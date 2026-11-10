<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CompletarRegistroGoogleRequest extends FormRequest
{
    /**
     * El registro_token cifrado prueba que la persona ya eligió su cuenta de Google.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Datos que el usuario llena en la ventana "Completa tu registro".
     * El correo no se recibe: sale del registro_token, que viene de Google.
     */
    public function rules(): array
    {
        return [
            'registro_token' => 'required|string|max:2048',
            'name'           => 'required|string|max:255',
            'rol'            => 'required|in:instructor,estudiante',
            'password'       => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * Mensajes en español para el usuario.
     */
    public function messages(): array
    {
        return [
            'registro_token.required' => 'Vuelve a intentarlo con Google.',
            'name.required'           => 'Escribe tu nombre.',
            'name.max'                => 'El nombre no puede pasar de 255 caracteres.',
            'rol.required'            => 'Elige si eres aprendiz o instructor.',
            'rol.in'                  => 'El rol elegido no es válido.',
            'password.required'       => 'Crea una contraseña.',
            'password.min'            => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'      => 'Las contraseñas no coinciden.',
        ];
    }
}
