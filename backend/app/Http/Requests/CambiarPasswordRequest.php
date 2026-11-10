<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CambiarPasswordRequest extends FormRequest
{
    /**
     * Cualquier usuario autenticado puede cambiar su propia contraseña.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * current_password compara con Hash::check la contraseña guardada del usuario.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password_actual' => 'required|string|current_password:sanctum',
            'password'        => 'required|string|min:8|confirmed|different:password_actual',
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
            'password_actual.required'         => 'Escribe tu contraseña actual.',
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'password.required'                => 'Escribe la nueva contraseña.',
            'password.min'                     => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'               => 'Las contraseñas nuevas no coinciden.',
            'password.different'               => 'La nueva contraseña debe ser distinta a la actual.',
        ];
    }
}
