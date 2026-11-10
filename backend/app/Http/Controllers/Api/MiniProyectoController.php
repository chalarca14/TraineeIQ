<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guia;
use App\Models\MiniProyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MiniProyectoController extends Controller
{
    /**
     * Lista los mini proyectos de las semanas que el usuario tiene abiertas, cada uno con
     * la entrega de ese usuario (o null). Nunca incluye la entrega de otro estudiante.
     * Sin paginar: una guia tiene como maximo 52 semanas de 3 mini proyectos.
     */
    public function porGuia(Request $request, int $guiaId): JsonResponse
    {
        $guia = Guia::with('grupo')->findOrFail($guiaId);
        Gate::authorize('view', $guia);

        $usuario = $request->user();

        // Misma regla que al leer una semana: lo bloqueado no aparece
        $semanasAbiertas = $guia->semanas()
            ->pluck('numero_semana', 'id')
            ->filter(fn (int $numero) => $guia->semanaLiberadaPara($usuario, $numero))
            ->keys();

        $miniProyectos = $guia->miniProyectos()
            ->select(['id', 'guia_id', 'guia_semana_id', 'orden', 'titulo', 'descripcion', 'dificultad'])
            ->whereIn('guia_semana_id', $semanasAbiertas)
            ->with([
                'semana:id,numero_semana,titulo',
                'entregas' => fn ($entregas) => $entregas
                    ->where('estudiante_id', $usuario->id)
                    ->select(['id', 'mini_proyecto_id', 'repo_url', 'estado', 'puntaje', 'mejor_puntaje', 'intentos', 'evaluado_en']),
            ])
            ->get()
            ->sortBy([['semana.numero_semana', 'asc'], ['orden', 'asc']])
            ->values()
            ->each(function (MiniProyecto $miniProyecto) {
                // Hay como maximo una entrega por estudiante: se entrega como objeto, no como lista
                $miniProyecto->setAttribute('entrega', $miniProyecto->entregas->first());
                $miniProyecto->unsetRelation('entregas');
            });

        return response()->json(['mini_proyectos' => $miniProyectos], 200);
    }

    /**
     * Devuelve el detalle de un mini proyecto: requisitos, buenas practicas y criterios.
     */
    public function show(int $id): JsonResponse
    {
        $miniProyecto = MiniProyecto::with(['guia.grupo', 'semana:id,numero_semana,titulo'])->findOrFail($id);
        Gate::authorize('view', $miniProyecto);

        return response()->json(['mini_proyecto' => $miniProyecto->makeHidden('guia')], 200);
    }
}
