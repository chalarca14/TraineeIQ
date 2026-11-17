<?php

namespace App\Http\Requests;

use App\Rules\TamanoExcelGuia;
use Illuminate\Foundation\Http\FormRequest;

class SubirMaterialSemanaRequest extends FormRequest
{
    /**
     * La autorización (instructor dueño de una guía de grupo) la resuelve GuiaPolicy en el controlador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mismas reglas de archivo que las guías: PDF hasta 10 MB o Excel hasta 2 MB.
     */
    public function rules(): array
    {
        return [
            'nombre'  => 'required|string|max:255',
            'archivo' => ['required', 'file', 'mimes:pdf,xlsx', 'max:10240', new TamanoExcelGuia()],
        ];
    }

    /**
     * Mensajes en español para el frontend.
     */
    public function messages(): array
    {
        return [
            'nombre.required'  => 'Escribe el nombre de la guía.',
            'nombre.max'       => 'El nombre es demasiado largo (máximo 255 caracteres).',
            'archivo.required' => 'Elige el archivo de la guía.',
            'archivo.mimes'    => 'La guía debe ser un PDF o un Excel (.xlsx).',
            'archivo.max'      => 'La guía no puede pesar más de 10 MB.',
        ];
    }
}
