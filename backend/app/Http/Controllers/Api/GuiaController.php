<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuiaController extends Controller
{
    //Listar todas la guias disponibles a las del usuario.
    public function index(Request $request)
    {
        $user = Auth::user();

        // Si es profesor, puede ver las guías que ha creado; si es estudiante, ve las guías generales o asignadas
        if ($user->role === 'profesor') {
            $guias = Guia::where('usuario_id', $user->id)->get();
        } else {
            $guias = Guia::all(); // O filtrar según el nivel/progreso del estudiante
        }

        return response()->json([
            'success' => true,
            'data' => $guias
        ], 200);
    }

    /**
     * Crear una nueva guía de estudio.
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'nivel' => 'required|string|in:principiante,intermedio,avanzado',
        ]);

        $guia = Guia::create([
            'usuario_id' => Auth::id(),
            'titulo' => $request->titulo,
            'contenido' => $request->contenido,
            'nivel' => $request->nivel,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Guía creada exitosamente',
            'data' => $guia
        ], 201);
    }

    /**
     * Ver el detalle de una guía específica.
     */
    public function show(int $id)
    {
        $guia = Guia::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $guia
        ], 200);
    }
}
