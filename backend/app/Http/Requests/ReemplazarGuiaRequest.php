<?php

namespace App\Http\Requests;

use App\Rules\TamanoExcelGuia;
use Illuminate\Foundation\Http\FormRequest;

class ReemplazarGuiaRequest extends FormRequest
{
    /**
     * La autorizacion sobre la guia la resuelve GuiaPolicy en el controlador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * confirmar_eliminacion autoriza borrar semanas que ya tienen entregas.
     */
    public function rules(): array
    {
        return [
            // PDF o Excel (.xlsx); el Excel tiene un límite de peso menor
            'archivo'               => ['required', 'file', 'mimes:pdf,xlsx', 'max:10240', new TamanoExcelGuia()],
            'confirmar_eliminacion' => 'sometimes|boolean',
        ];
    }

    /**
     * Mensajes en español para el archivo.
     */
    public function messages(): array
    {
        return [
            'archivo.required' => 'Elige el archivo de la guía.',
            'archivo.mimes'    => 'La guía debe ser un PDF o un Excel (.xlsx).',
            'archivo.max'      => 'La guía no puede pesar más de 10 MB.',
        ];
    }
}
