<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\EntregaEnEvaluacionException;
use App\Exceptions\EvaluacionNoPermitidaException;
use App\Exceptions\IaNoDisponibleException;
use App\Exceptions\RepositorioNoDisponibleException;
use App\Exceptions\RespuestaIaInvalidaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarEntregaRequest;
use App\Models\MiniProyecto;
use App\Services\EntregaMiniProyectoService;
use App\Services\EvaluarEntregaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class EntregaMiniProyectoController extends Controller
{
    /**
     * Devuelve la entrega del estudiante en este mini proyecto, o null si aun no tiene.
     * Siempre busca por el usuario de la sesion: nadie puede pedir la entrega de otro.
     */
    public function show(Request $request, EvaluarEntregaService $evaluador, int $id): JsonResponse
    {
        $miniProyecto = $this->buscarAutorizado($id);

        $entrega = $miniProyecto->entregas()->where('estudiante_id', $request->user()->id)->first();

        return response()->json([
            'entrega'            => $entrega,
            'intentos_restantes' => $evaluador->intentosRestantes($entrega),
        ], 200);
    }

    /**
     * Guarda o cambia el repositorio del estudiante.
     */
    public function guardar(GuardarEntregaRequest $request, EntregaMiniProyectoService $servicio, int $id): JsonResponse
    {
        $miniProyecto = $this->buscarAutorizado($id);

        try {
            $entrega = $servicio->guardarRepo($miniProyecto, $request->user(), $request->validated('repo_url'));
        } catch (EntregaEnEvaluacionException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            Log::error('Error guardando la entrega', ['mensaje' => $e->getMessage()]);

            return response()->json(['message' => 'Error al guardar el repositorio'], 500);
        }

        return response()->json(['entrega' => $entrega], $entrega->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Evalua el repositorio con la IA. La peticion espera el resultado.
     */
    public function evaluar(Request $request, EvaluarEntregaService $evaluador, int $id): JsonResponse
    {
        $miniProyecto = $this->buscarAutorizado($id);

        try {
            $entrega = $evaluador->evaluar($miniProyecto, $request->user());
        } catch (EvaluacionNoPermitidaException | RepositorioNoDisponibleException $e) {
            return response()->json(['message' => $e->getMessage()], $e->estadoHttp);
        } catch (IaNoDisponibleException | RespuestaIaInvalidaException $e) {
            Log::warning('No se pudo evaluar la entrega', ['mensaje' => $e->getMessage()]);

            return response()->json(['message' => 'La IA está ocupada, intenta de nuevo en un momento.'], 503);
        } catch (\Throwable $e) {
            Log::error('Error evaluando la entrega', ['mensaje' => $e->getMessage()]);

            return response()->json(['message' => 'Error al evaluar la entrega'], 500);
        }

        return response()->json([
            'entrega'            => $entrega,
            'intentos_restantes' => $evaluador->intentosRestantes($entrega),
        ], 200);
    }

    /**
     * Busca el mini proyecto y revisa que su semana este liberada para el usuario.
     */
    private function buscarAutorizado(int $id): MiniProyecto
    {
        $miniProyecto = MiniProyecto::with(['guia.grupo', 'semana:id,numero_semana'])->findOrFail($id);
        Gate::authorize('view', $miniProyecto);

        return $miniProyecto;
    }
}
