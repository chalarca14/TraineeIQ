<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registrar usuario.
     */
    public function register(Request $request)
    {
        // Mensajes en español: la app no tiene traducciones (APP_LOCALE=en) y el frontend muestra este
        // texto tal cual junto a cada campo. Antes el aprendiz veía "The email has already been taken."
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|min:8',
            'rol' => 'sometimes|string|in:estudiante,instructor',
        ], [
            'name.required' => 'Escribe tu nombre.',
            'name.max' => 'El nombre es demasiado largo (máximo 255 caracteres).',
            'email.required' => 'Escribe tu correo.',
            'email.email' => 'Escribe un correo válido.',
            'email.max' => 'El correo es demasiado largo (máximo 255 caracteres).',
            'email.unique' => 'Este correo ya está registrado. Inicia sesión o usa otro correo.',
            'password.required' => 'Escribe una contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'rol.in' => 'Elige un rol válido: aprendiz o instructor.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => $request->rol ?? 'estudiante', //Valor por defecto
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado correctamente',
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            // Mismas claves que login() (token, user): las usa el frontend para entrar al dashboard
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    /**
     * Iniciar sesión.
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first();

        // Tres casos con su propio mensaje (antes los tres decían "Credenciales incorrectas").
        // El ORDEN importa: el rol solo se revela DESPUÉS de comprobar la contraseña, así nadie
        // averigua qué rol tiene una cuenta sin conocer su contraseña.
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no registrado.',
            ], 401);
        }

        if (! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        // Contraseña correcta pero eligió la pestaña del otro rol: 403 (sabemos quién es, pero no
        // puede entrar por ahí). Mismo código que usa "Continuar con Google" para la pestaña equivocada.
        if ($user->rol !== $validated['rol']) {
            $pestana = $user->rol === 'instructor' ? 'Instructor' : 'Aprendiz';

            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta es de '.mb_strtolower($pestana).'. Elige «'.$pestana.'» para ingresar.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Cerrar sesión.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }
}
