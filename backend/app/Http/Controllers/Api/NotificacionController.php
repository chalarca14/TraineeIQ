<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Services\NotificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificacionController extends Controller
{
    /**
     * Recibe el servicio de avisos por inyeccion de dependencias.
     */
    public function __construct(private NotificacionService $notificaciones)
    {
    }

    /**
     * Lista los avisos del usuario, paginados, con el numero de no leidos para la campanita.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'no_leidas'      => $this->notificaciones->contarNoLeidas($request->user()),
            'notificaciones' => $this->notificaciones->listar($request->user()),
        ], 200);
    }

    /**
     * Marca un aviso como leido. Solo puede hacerlo quien lo recibio.
     */
    public function marcarLeida(int $id): JsonResponse
    {
        $notificacion = Notificacion::findOrFail($id);
        Gate::authorize('update', $notificacion);

        return response()->json([
            'notificacion' => $this->notificaciones->marcarLeida($notificacion),
        ], 200);
    }

    /**
     * Marca como leidos todos los avisos pendientes del usuario.
     */
    public function marcarTodas(Request $request): JsonResponse
    {
        return response()->json([
            'marcadas' => $this->notificaciones->marcarTodas($request->user()),
        ], 200);
    }
}
