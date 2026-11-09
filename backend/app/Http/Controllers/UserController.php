<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambiarPasswordRequest;
use App\Http\Requests\UpdatePreferredModeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        $user = $request->user();
        $validated = $request->validated();
        $user->modo_preferido = $validated['modo_preferido'];
        $user->save();

        return response()->json([
            'success' => true,
            'modo_preferido' => $user->modo_preferido,
        ]);
    }

    /**
     * Cambia la contraseña del usuario autenticado.
     * CambiarPasswordRequest ya comprobó que la contraseña actual es correcta.
     */
    public function updatePassword(CambiarPasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->password = Hash::make($request->validated('password'));
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente',
        ], 200);
    }
}
