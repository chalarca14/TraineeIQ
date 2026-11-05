<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GuiaNoProcesableException;
use App\Exceptions\IaNoDisponibleException;
use App\Exceptions\RespuestaIaInvalidaException;
use App\Exceptions\SemanasConEntregasException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReemplazarGuiaRequest;
use App\Http\Requests\StoreGuiaRequest;
use App\Models\Guia;
use App\Models\Grupo;
use App\Services\ProcesarGuiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class GuiaController extends Controller
{
    /**
     * Listar guías con acceso autorizado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->rol === 'instructor') {
            // El instructor ve las guías de sus grupos
            $gruposIds = Grupo::where('instructor_id', $user->id)->pluck('id');
            $guias = Guia::whereIn('grupo_id', $gruposIds)
                ->with(['temas' => fn($q) => $q->orderBy('orden', 'asc')])
                ->paginate(15);
        } else {
            // El estudiante ve sus guías personales y las de los grupos donde está inscrito
            $gruposInscritosIds = $user->gruposInscritos()->pluck('grupos.id');
            
            $guias = Guia::where(function ($query) use ($user, $gruposInscritosIds) {
                    $query->where('usuario_id', $user->id)
                          ->orWhereIn('grupo_id', $gruposInscritosIds);
                })
                ->with(['temas' => fn($q) => $q->orderBy('orden', 'asc')])
                ->paginate(15);
        }

        return response()->json($guias, 200);
    }

    /**
     * Sube una guía y la divide en semanas pendientes. El contenido de cada semana
     * se genera después, una por una, para no pasar el tiempo límite de la petición.
     */
    public function store(StoreGuiaRequest $request, ProcesarGuiaService $servicio): JsonResponse
    {
        $user = $request->user();
        $esInstructor = $user->rol === 'instructor';

        // El dueño sale de la sesión; nunca de lo que envía el frontend
        $datos = [
            'nombre'     => $request->nombre,
            'origen'     => $esInstructor ? 'grupo' : 'personal',
            'grupo_id'   => $esInstructor ? (int) $request->grupo_id : null,
            'usuario_id' => $esInstructor ? null : $user->id,
        ];

        try {
            $resultado = $servicio->crear($datos, $request->file('archivo'), $request->integer('semanas_totales') ?: null);
        } catch (\Throwable $e) {
            return $this->responderError($e);
        }

        return response()->json([
            'message'    => $resultado['creada'] ? 'Guía creada correctamente' : 'Esta guía ya estaba subida',
            'ya_existia' => ! $resultado['creada'],
            'guia'       => $this->cargarDetalle($resultado['guia']),
        ], $resultado['creada'] ? 201 : 200);
    }

    /**
     * Reemplaza el PDF de una guía. Solo vuelven a pendiente las semanas que cambiaron.
     * Con archivos, el frontend debe enviar POST con _method=PUT: PHP no lee archivos en PUT.
     */
    public function update(ReemplazarGuiaRequest $request, ProcesarGuiaService $servicio, int $id): JsonResponse
    {
        $guia = Guia::findOrFail($id);
        Gate::authorize('update', $guia);

        try {
            $resultado = $servicio->reemplazar($guia, $request->file('archivo'), $request->boolean('confirmar_eliminacion'));
        } catch (\Throwable $e) {
            return $this->responderError($e);
        }

        return response()->json([
            'message'           => 'Guía actualizada correctamente',
            'semanas_cambiadas' => $resultado['semanas_cambiadas'],
            'guia'              => $this->cargarDetalle($resultado['guia']->refresh()),
        ], 200);
    }

    /**
     * Carga los temas y las semanas que devuelven las respuestas de subida.
     */
    private function cargarDetalle(Guia $guia): Guia
    {
        return $guia->load(['temas' => fn ($q) => $q->orderBy('orden', 'asc'), 'semanas']);
    }

    /**
     * Traduce los errores del procesamiento a una respuesta amigable.
     */
    private function responderError(\Throwable $e): JsonResponse
    {
        if ($e instanceof GuiaNoProcesableException) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($e instanceof SemanasConEntregasException) {
            return response()->json(['message' => $e->getMessage(), 'semanas' => $e->semanas], 409);
        }

        Log::error('Error procesando la guía', ['mensaje' => $e->getMessage()]);

        if ($e instanceof IaNoDisponibleException || $e instanceof RespuestaIaInvalidaException) {
            return response()->json(['message' => 'La IA está ocupada, intenta de nuevo en un momento.'], 503);
        }

        return response()->json(['message' => 'Error al procesar la guía'], 500);
    }

    /**
     * Ver el detalle de una guía.
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $guia = Guia::with(['temas' => fn($q) => $q->orderBy('orden', 'asc')])->find($id);

        if (!$guia) {
            return response()->json(['message' => 'Guía no encontrada'], 404);
        }

        if ($user->rol === 'instructor') {
            $grupo = Grupo::find($guia->grupo_id);
            if (!$grupo || $grupo->instructor_id !== $user->id) {
                return response()->json(['message' => 'No tienes acceso a esta guía.'], 403);
            }
        } else {
            $gruposInscritosIds = $user->gruposInscritos()->pluck('grupos.id')->toArray();
            
            $tieneAcceso = ($guia->usuario_id === $user->id) || 
                        ($guia->grupo_id && in_array($guia->grupo_id, $gruposInscritosIds));

            if (!$tieneAcceso) {
                return response()->json(['message' => 'No tienes acceso a esta guía.'], 403);
            }
        }

        return response()->json(['guia' => $guia], 200);
    }
}