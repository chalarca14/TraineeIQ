<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePreferredModeRequest;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Consultar el modo preferido del estudiante autenticado.
     */
    public function showMode(Request $request)
    {
        return response()->json([
            'success' => true,
            'modo_preferido' => $request->user()->modo_preferido,
        ]);
    }

    /**
     * Actualizar el modo preferido del estudiante autenticado.
     */
    public function updateMode(UpdatePreferredModeRequest $request)
    {
        $request->user()->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Modo preferido actualizado correctamente.',
            'modo_preferido' => $request->user()->modo_preferido,
        ]);
    }
}
