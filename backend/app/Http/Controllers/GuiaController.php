<?php

namespace App\Http\Controllers;

use App\Models\Guia;
use App\Models\GuiaTema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GuiaController extends Controller
{
    // Listar todas las guías
    public function index()
    {
        $guias = Guia::with('temas')->get();
        return response()->json($guias, 200);
    }

    // Crear una nueva guía con sus temas
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'grupo_id' => 'nullable|exists:grupos,id',
            'origen' => 'required|in:grupo,personal',
            'semanas_totales' => 'required|integer|min:1',
            'temas' => 'required|array|min:1',
            'temas.*.nombre_tema' => 'required|string|max:255',
            'temas.*.descripcion' => 'nullable|string',
            'temas.*.orden' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Crear la Guía
            $guia = Guia::create([
                'nombre' => $request->nombre,
                'grupo_id' => $request->grupo_id,
                'usuario_id' => $request->user()->id,
                'origen' => $request->origen,
                'semanas_totales' => $request->semanas_totales,
            ]);

            // 2. Crear los Temas asociados
            foreach ($request->temas as $tema) {
                GuiaTema::create([
                    'guia_id' => $guia->id,
                    'nombre_tema' => $tema['nombre_tema'],
                    'descripcion' => $tema['descripcion'] ?? null,
                    'orden' => $tema['orden'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Guía de aprendizaje creada exitosamente',
                'guia' => $guia->load('temas')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la guía',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Ver una guía específica
    public function show(int $id)
    {
        $guia = Guia::with(['temas', 'miniProyectos'])->find($id);

        if (!$guia) {
            return response()->json(['message' => 'Guía no encontrada'], 404);
        }

        return response()->json($guia, 200);
    }
}