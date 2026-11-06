<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarSemanaActualRequest;
use App\Models\EntregaMiniProyecto;
use App\Models\Grupo;
use App\Models\EstudianteGrupo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class GrupoController extends Controller
{
    //Listar los grupos del instructor o a los que pertenece el estudiante.

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->rol === 'instructor') {
            $grupos = Grupo::where('instructor_id', $user->id)->withCount('estudiantes')->get();
        } else {
            // Si es estudiante, listar los grupos en los que está inscrito
            $grupos = $user->gruposInscritos()->with('instructor:id,name')->get();
        }

        return response()->json([
            'success' => true,
            'data' => $grupos
        ], 200);
    }

    /**
     * Crear un nuevo grupo (Solo instructores).
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        // Generar un código de acceso único de 6 caracteres alfanuméricos
        do {
            $codigo = Str::upper(Str::random(6));
        } while (Grupo::where('codigo_acceso', $codigo)->exists());

        $grupo = Grupo::create([
            'instructor_id' => Auth::id(),
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'codigo_acceso' => $codigo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grupo creado exitosamente',
            'data' => $grupo
        ], 201);
    }

    /**
     * Unirse a un grupo mediante código de acceso (Estudiantes).
     */
    public function unirse(Request $request)
    {
        $request->validate([
            'codigo_acceso' => 'required|string|exists:grupos,codigo_acceso',
        ]);

        $grupo = Grupo::where('codigo_acceso', $request->codigo_acceso)->first();
        $estudianteId = Auth::id();

        // Verificar si ya está inscrito
        $inscrito = EstudianteGrupo::where('grupo_id', $grupo->id)
            ->where('estudiante_id', $estudianteId)
            ->exists();

        if ($inscrito) {
            return response()->json([
                'success' => false,
                'message' => 'Ya estás inscrito en este grupo.'
            ], 400);
        }

        // Registrar inscripción
        EstudianteGrupo::create([
            'grupo_id' => $grupo->id,
            'estudiante_id' => $estudianteId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Te has unido al grupo exitosamente',
            'data' => $grupo
        ], 200);
    }

    /**
     * Ver detalles de un grupo específico y sus estudiantes inscritos.
     * Solo lo ven su instructor y sus estudiantes, porque incluye nombres y correos.
     */
    public function show(int $id)
    {
        $grupo = Grupo::with(['estudiantes:id,name,email', 'instructor:id,name'])->findOrFail($id);
        Gate::authorize('view', $grupo);

        return response()->json([
            'success' => true,
            'data' => $grupo
        ], 200);
    }

    /**
     * Libera semanas: los estudiantes del grupo pueden leer hasta la semana indicada.
     * Aplica a todas las guias del grupo. Bajar el numero vuelve a bloquear las siguientes.
     */
    public function actualizarSemanaActual(ActualizarSemanaActualRequest $request, int $id): JsonResponse
    {
        $grupo = Grupo::findOrFail($id);
        Gate::authorize('update', $grupo);

        $grupo->update(['semana_actual' => $request->integer('semana_actual')]);

        return response()->json([
            'message'       => 'Semana actualizada correctamente',
            'semana_actual' => $grupo->semana_actual,
        ], 200);
    }

    /**
     * Lista el repositorio y el puntaje de los estudiantes en los mini proyectos del grupo.
     * Filtra por las guias del grupo, asi que nunca incluye entregas del Modo Personal.
     */
    public function entregas(int $id): JsonResponse
    {
        $grupo = Grupo::findOrFail($id);
        Gate::authorize('verEntregas', $grupo);

        $entregas = EntregaMiniProyecto::query()
            ->select(['id', 'mini_proyecto_id', 'estudiante_id', 'repo_url', 'estado', 'puntaje', 'intentos', 'evaluado_en', 'updated_at'])
            ->whereHas('miniProyecto.guia', fn ($guias) => $guias->where('grupo_id', $grupo->id))
            ->with([
                'estudiante:id,name,email',
                'miniProyecto:id,guia_id,guia_semana_id,titulo,dificultad',
                'miniProyecto.guia:id,nombre',
                'miniProyecto.semana:id,numero_semana',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(30);

        return response()->json($entregas, 200);
    }
}
