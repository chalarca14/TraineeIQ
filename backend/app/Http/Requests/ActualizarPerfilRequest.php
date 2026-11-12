<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarPerfilRequest extends FormRequest
{
    /**
     * Cualquier usuario autenticado puede cambiar su propio nombre.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Solo el nombre es editable. El correo no: es con lo que se inicia sesión (también con Google)
     * y cambiarlo sin verificarlo podría dejar a alguien fuera de su cuenta.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:255',
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
            'name.required' => 'Escribe tu nombre.',
            'name.min'      => 'El nombre debe tener al menos 2 caracteres.',
            'name.max'      => 'El nombre es demasiado largo (máximo 255 caracteres).',
        ];
    }
}
