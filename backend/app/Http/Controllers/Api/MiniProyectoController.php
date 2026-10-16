<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MiniProyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MiniProyectoController extends Controller
{
    //Listar los mini proyectos disponibles
    public function index(Request $request)
    {
        $miniProyectos = MiniProyecto::all();

        return response()->json([
            'success' => true,
            'data' => $miniProyectos
        ], 200);
    }

    /**
     * Crear un nuevo mini proyecto (Rol profesor/instructor).
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'dificultad' => 'required|string|in:facil,intermedio,dificil',
        ]);

        $miniProyecto = MiniProyecto::create([
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'dificultad' => $request->dificultad,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mini proyecto creado exitosamente',
            'data' => $miniProyecto
        ], 201);
    }

    /**
     * Ver los detalles de un mini proyecto.
     */
    public function show(int $id)
    {
        $miniProyecto = MiniProyecto::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $miniProyecto
        ], 200);
    }
}
