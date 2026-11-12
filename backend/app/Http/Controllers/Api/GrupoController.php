<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarSemanaActualRequest;
use App\Http\Requests\FiltrarEntregasGrupoRequest;
use App\Models\EntregaMiniProyecto;
use App\Models\Grupo;
use App\Models\EstudianteGrupo;
use App\Services\NotificacionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GrupoController extends Controller
{
    /**
     * Lista los grupos del instructor o a los que pertenece el estudiante,
     * con la cantidad de guías y de mini proyectos para las tarjetas de resumen.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->rol === 'instructor') {
            $grupos = Grupo::where('instructor_id', $user->id)
                ->withCount(['estudiantes', 'guias', 'miniProyectos'])
                ->get();
        } else {
            // El estudiante solo cuenta los mini proyectos de semanas ya liberadas,
            // para que el número coincida con lo que puede abrir
            $grupos = $user->gruposInscritos()
                ->with('instructor:id,name')
                ->withCount(['guias', 'miniProyectos' => fn ($proyectos) => $proyectos->whereHas(
                    'semana',
                    fn ($semanas) => $semanas->whereColumn('guia_semanas.numero_semana', '<=', 'grupos.semana_actual')
                )])
                ->get();
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
        // Los códigos se generan en mayúsculas: así "abc123 " también funciona
        $request->merge(['codigo_acceso' => Str::upper(trim((string) $request->input('codigo_acceso')))]);

        $request->validate([
            'codigo_acceso' => 'required|string|exists:grupos,codigo_acceso',
        ], [
            'codigo_acceso.required' => 'Escribe el código del grupo.',
            'codigo_acceso.exists'   => 'Ese código no existe. Revisa que esté bien escrito.',
        ]);

        $grupo = Grupo::where('codigo_acceso', $request->codigo_acceso)->first();
        $estudianteId = Auth::id();

        // Verificar si ya está inscrito
        $inscrito = EstudianteGrupo::where('grupo_id', $grupo->id)
            ->where('estudiante_id', $estudianteId)
            ->exists();

        if ($inscrito) {
            return $this->yaInscrito();
        }

        try {
            EstudianteGrupo::create([
                'grupo_id' => $grupo->id,
                'estudiante_id' => $estudianteId,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Dos clics a la vez: la base ya tiene la inscripción
            return $this->yaInscrito();
        }

        return response()->json([
            'success' => true,
            'message' => 'Te has unido al grupo exitosamente',
            'data' => $grupo
        ], 200);
    }

    /**
     * Respuesta cuando el estudiante ya pertenece al grupo.
     */
    private function yaInscrito(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Ya estás inscrito en este grupo.'
        ], 409);
    }

    /**
     * Borra el grupo (solo su instructor). La base borra en cascada sus inscripciones,
     * sus guías con todo su contenido y las entregas de sus estudiantes, así que el
     * código deja de funcionar. Después se borran los PDF del disco.
     */
    public function destroy(int $id): JsonResponse
    {
        $grupo = Grupo::findOrFail($id);
        Gate::authorize('delete', $grupo);

        $pdfs = $grupo->guias()->whereNotNull('archivo_pdf')->pluck('archivo_pdf')->all();

        DB::transaction(fn () => $grupo->delete());

        try {
            Storage::disk('public')->delete($pdfs);
        } catch (\Throwable $e) {
            // El grupo ya se borró: un PDF que quede en disco no afecta a nadie
            Log::warning('No se pudieron borrar los PDF del grupo', ['mensaje' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Grupo borrado correctamente',
        ], 200);
    }

    /**
     * Ver detalles de un grupo específico y sus estudiantes inscritos.
     * Solo lo ven su instructor y sus estudiantes, porque incluye nombres y correos.
     * ultima_semana es la semana más alta entre las guías del grupo (null si no tiene guías):
     * sirve para saber hasta dónde se puede liberar.
     */
    public function show(int $id)
    {
        $grupo = Grupo::with(['estudiantes:id,name,email', 'instructor:id,name'])
            ->withMax('guias as ultima_semana', 'semanas_totales')
            ->findOrFail($id);
        Gate::authorize('view', $grupo);

        return response()->json([
            'success' => true,
            'data' => $grupo
        ], 200);
    }

    /**
     * Libera semanas: los estudiantes del grupo pueden leer hasta la semana indicada.
     * Aplica a todas las guias del grupo. Bajar el numero vuelve a bloquear las siguientes.
     * Si la semana sube, avisa a los estudiantes en la campanita.
     */
    public function actualizarSemanaActual(ActualizarSemanaActualRequest $request, int $id, NotificacionService $notificaciones): JsonResponse
    {
        $grupo = Grupo::findOrFail($id);
        Gate::authorize('update', $grupo);

        $anterior = (int) $grupo->semana_actual;
        $grupo->update(['semana_actual' => $request->integer('semana_actual')]);

        // Solo avisa si la semana sube: bajarla o dejarla igual no libera nada nuevo
        if ($grupo->semana_actual > $anterior) {
            try {
                $notificaciones->avisarSemanaLiberada($grupo, $grupo->semana_actual, $anterior);
            } catch (\Throwable $e) {
                // La semana ya quedó liberada: un aviso que falla no debe deshacerla
                Log::error('No se pudo avisar de la semana liberada', ['grupo_id' => $grupo->id, 'mensaje' => $e->getMessage()]);
            }
        }

        return response()->json([
            'message'       => 'Semana actualizada correctamente',
            'semana_actual' => $grupo->semana_actual,
        ], 200);
    }

    /**
     * Lista el repositorio y el puntaje de los estudiantes en los mini proyectos del grupo.
     * Filtra por las guias del grupo, asi que nunca incluye entregas del Modo Personal.
     * Acepta filtros opcionales por mini proyecto, estudiante y estado; se suman entre si.
     */
    public function entregas(FiltrarEntregasGrupoRequest $request, int $id): JsonResponse
    {
        $grupo = Grupo::findOrFail($id);
        Gate::authorize('verEntregas', $grupo);

        $entregas = EntregaMiniProyecto::query()
            ->select(['id', 'mini_proyecto_id', 'estudiante_id', 'repo_url', 'estado', 'puntaje', 'intentos', 'evaluado_en', 'updated_at'])
            ->whereHas('miniProyecto.guia', fn ($guias) => $guias->where('grupo_id', $grupo->id))
            ->when($request->filled('mini_proyecto_id'), fn ($q) => $q->where('mini_proyecto_id', $request->integer('mini_proyecto_id')))
            ->when($request->filled('estudiante_id'), fn ($q) => $q->where('estudiante_id', $request->integer('estudiante_id')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->input('estado')))
            ->with([
                'estudiante:id,name,email',
                'miniProyecto:id,guia_id,guia_semana_id,titulo,dificultad',
                'miniProyecto.guia:id,nombre',
                'miniProyecto.semana:id,numero_semana',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString(); // los enlaces de otras páginas conservan los filtros

        return response()->json($entregas, 200);
    }
}
