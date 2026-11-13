<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\OlvidePasswordRequest;
use App\Http\Requests\Auth\RestablecerPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

/**
 * "¿Olvidaste tu contraseña?". Usa el password broker de Laravel: él crea el token, lo guarda
 * cifrado en password_reset_tokens, lo vence a los 60 minutos (config/auth.php) y limita
 * los envíos repetidos al mismo correo.
 */
class RecuperarPasswordController extends Controller
{
    /**
     * Envía el correo con el enlace para crear una contraseña nueva.
     * Responde SIEMPRE lo mismo, exista o no el correo: así nadie puede averiguar
     * qué correos tienen cuenta en TraineeIQ.
     */
    public function enviarEnlace(OlvidePasswordRequest $request): JsonResponse
    {
        try {
            Password::sendResetLink(['email' => $request->validated('email')]);
        } catch (Throwable $e) {
            // Si el servicio de correo falla, se responde igual: un 500 solo para los correos
            // registrados revelaría cuáles tienen cuenta. El correo de la persona no va al log
            Log::error('No se pudo enviar el correo de recuperación de contraseña', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Si el correo está registrado, te enviamos un enlace para crear una nueva contraseña. Revisa tu bandeja de entrada.',
        ], 200);
    }

    /**
     * Guarda la contraseña nueva si el token del enlace es válido y no ha vencido.
     * Cierra todas las sesiones abiertas (borra los tokens de Sanctum): si alguien más
     * conocía la contraseña vieja, queda fuera.
     */
    public function restablecer(RestablecerPasswordRequest $request): JsonResponse
    {
        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $usuario, string $password) {
                $usuario->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $usuario->tokens()->delete();

                event(new PasswordReset($usuario));
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            // Token inválido, vencido o correo que no coincide: un solo mensaje para todo
            return response()->json([
                'success' => false,
                'message' => 'El enlace no es válido o ya venció. Pide uno nuevo.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tu contraseña se actualizó. Ya puedes iniciar sesión.',
        ], 200);
    }
}
