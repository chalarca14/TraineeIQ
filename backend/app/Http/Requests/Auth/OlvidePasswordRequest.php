<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OlvidePasswordRequest extends FormRequest
{
    /**
     * Ruta pública: cualquiera puede pedir el enlace (no hay sesión porque olvidó la contraseña).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Solo se valida el formato del correo. Si existe o no, NO se dice en la respuesta.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
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
            'email.required' => 'Escribe tu correo.',
            'email.email'    => 'Escribe un correo válido.',
            'email.max'      => 'El correo es demasiado largo.',
        ];
    }
}
