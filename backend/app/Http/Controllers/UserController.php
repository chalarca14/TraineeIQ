<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarPerfilRequest;
use App\Http\Requests\CambiarPasswordRequest;
use App\Http\Requests\UpdatePreferredModeRequest;
use App\Services\ExperienciaService;
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
     * Devuelve la experiencia total del estudiante en sus guias personales.
     */
    public function experiencia(Request $request, ExperienciaService $experiencia): JsonResponse
    {
        return response()->json([
            'success'  => true,
            'xp_total' => $experiencia->xpTotal($request->user()),
        ], 200);
    }

    /**
     * Cambia el nombre del usuario autenticado (ambos roles). Devuelve el usuario para que
     * el frontend actualice lo que tiene guardado sin volver a pedirlo.
     */
    public function updateProfile(ActualizarPerfilRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->name = $request->validated('name');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Tu nombre se actualizó correctamente.',
            'usuario' => $user,
        ], 200);
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
