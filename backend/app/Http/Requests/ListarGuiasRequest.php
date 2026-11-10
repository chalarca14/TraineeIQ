<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListarGuiasRequest extends FormRequest
{
    /**
     * Cualquier usuario autenticado lista sus guias; el controlador limita cuales ve.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Filtro opcional por grupo. Solo reduce la lista: nunca da acceso a guias ajenas.
     */
    public function rules(): array
    {
        return [
            'grupo_id' => 'nullable|integer|min:1',
        ];
    }
}
