<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarGrupoRequest extends FormRequest
{
    /**
     * La autorización sobre el grupo la resuelve GrupoPolicy en el controlador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Solo se editan el nombre y la descripción. El código de acceso, la semana liberada y el
     * instructor no se reciben aquí: aunque lleguen en la petición, se ignoran.
     */
    public function rules(): array
    {
        return [
            'nombre'      => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Mensajes en español para el frontend.
     */
    public function messages(): array
    {
        return [
            'nombre.required'  => 'Escribe el nombre del grupo.',
            'nombre.max'       => 'El nombre es demasiado largo (máximo 255 caracteres).',
            'descripcion.max'  => 'La descripción es demasiado larga (máximo 1000 caracteres).',
        ];
    }
}
