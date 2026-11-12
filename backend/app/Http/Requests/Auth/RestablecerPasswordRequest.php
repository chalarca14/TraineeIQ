<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RestablecerPasswordRequest extends FormRequest
{
    /**
     * Ruta pública: quien tiene el enlace del correo puede crear una contraseña nueva.
     * La seguridad está en el token, que valida el controlador con el password broker.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mismas reglas de contraseña que el registro y el cambio de contraseña (mínimo 8).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token'    => 'required|string',
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * Mensajes en español para el frontend.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required'     => 'El enlace no es válido. Pide uno nuevo.',
            'email.required'     => 'El enlace no es válido. Pide uno nuevo.',
            'email.email'        => 'El enlace no es válido. Pide uno nuevo.',
            'password.required'  => 'Escribe la nueva contraseña.',
            'password.min'       => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
