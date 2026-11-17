<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GuiaNoProcesableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubirMaterialSemanaRequest;
use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Services\MaterialSemanaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Guías de la semana: guías detalladas que el instructor adjunta a una semana de la guía
 * principal. La lógica está en MaterialSemanaService.
 */
class MaterialSemanaController extends Controller
{
    /**
     * Lista las guías de una semana. Las ve quien puede leer la semana: el instructor dueño
     * siempre, y el aprendiz solo si la semana ya está liberada.
     */
    public function index(int $guiaId, int $numero): JsonResponse
    {
        [, $semana] = $this->semana($guiaId, $numero, 'viewSemana');

        return response()->json(['materiales' => $semana->materiales], 200);
    }

    /**
     * Adjunta una guía a la semana (solo el instructor del grupo). semana_lista dice si la
     * semana ya tenía contenido generado, para que el frontend ofrezca "Actualizar".
     */
    public function store(SubirMaterialSemanaRequest $request, MaterialSemanaService $servicio, int $guiaId, int $numero): JsonResponse
    {
        [, $semana] = $this->semana($guiaId, $numero, 'gestionarMateriales');

        try {
            $material = $servicio->agregar($semana, $request->validated('nombre'), $request->file('archivo'));
        } catch (GuiaNoProcesableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('No se pudo adjuntar la guía de la semana', ['guia_semana_id' => $semana->id, 'mensaje' => $e->getMessage()]);

            return response()->json(['message' => 'No se pudo adjuntar la guía. Intenta de nuevo.'], 500);
        }

        return response()->json([
            'message'      => 'Guía de la semana agregada.',
            'material'     => $material,
            'semana_lista' => $semana->estado_generacion === 'listo',
        ], 201);
    }

    /**
     * Quita una guía de la semana (solo el instructor del grupo).
     */
    public function destroy(MaterialSemanaService $servicio, int $guiaId, int $numero, int $id): JsonResponse
    {
        [, $semana] = $this->semana($guiaId, $numero, 'gestionarMateriales');

        $servicio->quitar($semana->materiales()->findOrFail($id));

        return response()->json(['message' => 'Guía de la semana quitada.'], 200);
    }

    /**
     * Busca la guía y su semana (404 si no existen) y revisa el permiso indicado (403).
     *
     * @return array{0: Guia, 1: GuiaSemana}
     */
    private function semana(int $guiaId, int $numero, string $permiso): array
    {
        $guia = Guia::with('grupo')->findOrFail($guiaId);
        Gate::authorize('view', $guia);

        $semana = $guia->semanas()->where('numero_semana', $numero)->firstOrFail();
        Gate::authorize($permiso, $permiso === 'viewSemana' ? [$guia, $numero] : $guia);

        return [$guia, $semana->setRelation('guia', $guia)];
    }
}
