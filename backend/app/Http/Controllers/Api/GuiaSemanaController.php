<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\IaNoDisponibleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerarSemanaRequest;
use App\Models\Guia;
use App\Services\GenerarContenidoSemanaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class GuiaSemanaController extends Controller
{
    /**
     * Genera el contenido de una semana. Lo llama el frontend, una semana por vez.
     * Cualquier miembro puede generar lo pendiente; forzar es solo para el dueño.
     * Responde 200 con el estado que quedo, aunque sea 'generando' o 'error'.
     */
    public function generar(
        GenerarSemanaRequest $request,
        GenerarContenidoSemanaService $servicio,
        int $guiaId,
        int $numero
    ): JsonResponse {
        $guia = Guia::findOrFail($guiaId);
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

        return response()->json([
            'semana' => $semana->load(['recomendaciones', 'miniProyectos']),
        ], 200);
    }
}
