<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use App\Models\EstudianteGrupo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GrupoController extends Controller
{
    //Listar los grupos del profesor o a los que pertenece el estudiante.

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'profesor'){
            $grupo = Grupo::where('profesor_id', $user->id)->withCount ('estudiantes')->get();
            } else {
            // Si es estudiante, listar los grupos en los que está inscrito
            $grupos = $user->gruposInscritos()->with('profesor:id,name')->get();
        }

        return response()->json([
            'success' => true,
            'data' => $grupos
        ], 200);
    }

    /**
     * Crear un nuevo grupo (Solo profesores).
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
            'profesor_id' => Auth::id(),
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
     */
    public function show(int $id)
    {
        $grupo = Grupo::with(['estudiantes:id,name,email', 'profesor:id,name'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $grupo
        ], 200);
    }
}
