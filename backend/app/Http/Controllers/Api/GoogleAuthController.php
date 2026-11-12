<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AccesoGoogleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompletarRegistroGoogleRequest;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Services\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Recibe el servicio con la lógica de "Continuar con Google".
     */
    public function __construct(private readonly GoogleAuthService $google)
    {
    }

    /**
     * Entra con Google. Si la cuenta es nueva, responde requiere_registro para abrir
     * la ventana "Completa tu registro"; si ya existe, responde igual que POST /login.
     */
    public function entrar(GoogleLoginRequest $request): JsonResponse
    {
        try {
            $resultado = $this->google->entrar($request->input('credential'), $request->input('rol'));
        } catch (AccesoGoogleException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->estado);
        } catch (\Throwable $e) {
            Log::error('Error al entrar con Google', ['mensaje' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'No se pudo iniciar sesión con Google.'], 500);
        }

        if (isset($resultado['requiere_registro'])) {
            return response()->json(['success' => true] + $resultado, 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'token'   => $resultado['token'],
            'user'    => $resultado['usuario'],
        ], 200);
    }

    /**
     * Crea la cuenta de un usuario nuevo de Google con su rol y contraseña.
     * Responde igual que POST /register para que el frontend entre al dashboard.
     */
    public function completarRegistro(CompletarRegistroGoogleRequest $request): JsonResponse
    {
        try {
            $resultado = $this->google->completarRegistro(
                $request->input('registro_token'),
                $request->input('name'),
                $request->input('rol'),
                $request->input('password')
            );
        } catch (AccesoGoogleException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->estado);
        } catch (\Throwable $e) {
            Log::error('Error al completar el registro con Google', ['mensaje' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'No se pudo completar el registro.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado correctamente',
            'token'   => $resultado['token'],
            'user'    => $resultado['usuario'],
        ], 201);
    }
}
