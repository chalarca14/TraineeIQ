<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\IaNoDisponibleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerarSemanaRequest;
use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Services\GenerarContenidoSemanaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class GuiaSemanaController extends Controller
{
    /**
     * Lista todas las semanas de una guia con su estado. Las que aun no estan
     * liberadas para el usuario llegan con disponible en false.
     * Sin paginar: una guia tiene como maximo 52 semanas y la vista las necesita todas.
     */
    public function index(Request $request, int $guiaId): JsonResponse
    {
        $guia = Guia::with('grupo')->findOrFail($guiaId);
        Gate::authorize('view', $guia);

        // El texto completo de cada semana es pesado y aqui no se usa
        $semanas = $guia->semanas()
            ->select(['id', 'guia_id', 'numero_semana', 'titulo', 'resumen', 'estado_generacion', 'error_mensaje'])
            ->get()
            ->each(fn (GuiaSemana $semana) => $this->marcarDisponible($semana, $guia, $request));

        return response()->json(['semanas' => $semanas], 200);
    }

    /**
     * Devuelve las recomendaciones, los mini proyectos y los temas de una semana.
     * Una semana que aun no esta liberada responde 403.
     */
    public function show(Request $request, int $guiaId, int $numero): JsonResponse
    {
        $guia = Guia::with('grupo')->findOrFail($guiaId);
        Gate::authorize('view', $guia);

        $semana = $guia->semanas()->where('numero_semana', $numero)->firstOrFail();
        Gate::authorize('viewSemana', [$guia, $numero]);

        $this->marcarDisponible($semana, $guia, $request);

        return response()->json([
            'semana' => $semana->load(['recomendaciones', 'miniProyectos', 'temas']),
        ], 200);
    }

    /**
     * Genera el contenido de una semana. Lo llama el frontend, una semana por vez.
     * Cualquier miembro puede generar lo pendiente; forzar es solo para el dueño.
     * Responde 200 con el estado que quedo, aunque sea 'generando' o 'error'.
     * Una semana bloqueada se genera igual, pero su contenido no viaja en la respuesta.
     */
    public function generar(
        GenerarSemanaRequest $request,
        GenerarContenidoSemanaService $servicio,
        int $guiaId,
        int $numero
    ): JsonResponse {
        $guia = Guia::with('grupo')->findOrFail($guiaId);
        Gate::authorize('view', $guia);

        $forzar = $request->boolean('forzar');

        if ($forzar) {
            Gate::authorize('update', $guia);
        }

        $semana = $guia->semanas()->where('numero_semana', $numero)->firstOrFail();

        try {
            $semana = $servicio->generar($semana, $forzar);
        } catch (IaNoDisponibleException $e) {
            return response()->json([
                'message' => 'La IA está ocupada. La semana se preparará más tarde.',
            ], 503);
        } catch (\Throwable $e) {
            Log::error('Error generando la semana', ['mensaje' => $e->getMessage()]);

            return response()->json(['message' => 'Error al generar la semana'], 500);
        }

        $this->marcarDisponible($semana, $guia, $request);

        if ($semana->disponible) {
            $semana->load(['recomendaciones', 'miniProyectos']);
        }

        return response()->json(['semana' => $semana], 200);
    }

    /**
     * Agrega a la semana el dato disponible, que el frontend usa para bloquearla.
     * En las guias personales agrega tambien su experiencia: obtenida, maxima y requerida.
     */
    private function marcarDisponible(GuiaSemana $semana, Guia $guia, Request $request): void
    {
        $semana->setAttribute('disponible', $guia->semanaLiberadaPara($request->user(), $semana->numero_semana));

        if ($guia->origen !== 'personal') {
            return;
        }

        $xp = $guia->progresoExperiencia()->get($semana->numero_semana, []);

        foreach ($xp as $campo => $valor) {
            $semana->setAttribute($campo, $valor);
        }
    }
}
