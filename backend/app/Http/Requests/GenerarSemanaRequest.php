<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerarSemanaRequest extends FormRequest
{
    /**
     * La autorizacion sobre la guia la resuelve GuiaPolicy en el controlador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * forzar regenera la semana aunque este lista o en error (solo el dueño).
     */
    public function rules(): array
    {
        return [
            'forzar' => 'sometimes|boolean',
        ];
    }
}
