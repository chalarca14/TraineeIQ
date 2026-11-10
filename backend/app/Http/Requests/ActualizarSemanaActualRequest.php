<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarSemanaActualRequest extends FormRequest
{
    /**
     * La autorizacion sobre el grupo la resuelve GrupoPolicy en el controlador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El tope de 52 es el mismo que se acepta para las semanas de una guia.
     */
    public function rules(): array
    {
        return [
            'semana_actual' => 'required|integer|min:1|max:52',
        ];
    }
}
