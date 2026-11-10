<?php

namespace App\Http\Requests;

use App\Models\Grupo;
use Illuminate\Foundation\Http\FormRequest;

class StoreGuiaRequest extends FormRequest
{
    /**
     * Un instructor solo puede subir guias a sus propios grupos.
     * usuario_id nunca se recibe del frontend: sale siempre de la sesion.
     */
    public function authorize(): bool
    {
        if ($this->user()->rol !== 'instructor' || ! $this->filled('grupo_id')) {
            return true; // Las reglas de abajo validan estos casos
        }

        $grupo = Grupo::find($this->input('grupo_id'));

        return ! $grupo || $grupo->instructor_id === $this->user()->id;
    }

    /**
     * El instructor debe indicar el grupo; el estudiante no puede enviarlo,
     * porque sus guias siempre son personales.
     */
    public function rules(): array
    {
        $esInstructor = $this->user()->rol === 'instructor';

        return [
            'nombre'          => 'required|string|max:255',
            'archivo'         => 'required|file|mimes:pdf|max:10240',
            'grupo_id'        => $esInstructor ? 'required|integer|exists:grupos,id' : 'prohibited',
            'semanas_totales' => 'nullable|integer|min:1|max:52',
        ];
    }
}
