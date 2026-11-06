<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MiniProyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MiniProyectoController extends Controller
{
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
