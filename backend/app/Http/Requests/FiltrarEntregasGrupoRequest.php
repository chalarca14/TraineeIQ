<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarEntregasGrupoRequest extends FormRequest
{
    /**
     * La autorizacion sobre el grupo la resuelve GrupoPolicy en el controlador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Filtros opcionales de la lista de entregas; sin ellos se listan todas.
     */
    public function rules(): array
    {
        return [
            'mini_proyecto_id' => 'nullable|integer|min:1',
            'estudiante_id'    => 'nullable|integer|min:1',
            'estado'           => 'nullable|in:pendiente,evaluando,evaluado,error',
        ];
    }
}
