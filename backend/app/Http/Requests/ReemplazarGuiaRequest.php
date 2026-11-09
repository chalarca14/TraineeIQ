<?php

namespace App\Http\Requests;

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
            'archivo'               => 'required|file|mimes:pdf|max:10240',
            'confirmar_eliminacion' => 'sometimes|boolean',
        ];
    }
}
