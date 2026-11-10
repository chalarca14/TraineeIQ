<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class GoogleLoginRequest extends FormRequest
{
    /**
     * Cualquiera puede intentar entrar con Google; el servicio verifica la cuenta.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El ID token que entrega el botón de Google y la pestaña de rol elegida.
     */
    public function rules(): array
    {
        return [
            'credential' => 'required|string|max:4096',
            'rol'        => 'required|in:instructor,estudiante',
        ];
    }

    /**
     * Mensajes en español para el usuario.
     */
    public function messages(): array
    {
        return [
            'credential.required' => 'No recibimos la respuesta de Google. Intenta de nuevo.',
            'rol.required'        => 'Elige si eres aprendiz o instructor.',
            'rol.in'              => 'El rol elegido no es válido.',
        ];
    }
}
