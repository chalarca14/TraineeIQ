<?php

namespace App\Http\Requests;

use App\Models\Grupo;
use App\Rules\TamanoExcelGuia;
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
            // PDF o Excel (.xlsx); el Excel tiene un límite de peso menor
            'archivo'         => ['required', 'file', 'mimes:pdf,xlsx', 'max:10240', new TamanoExcelGuia()],
            'grupo_id'        => $esInstructor ? 'required|integer|exists:grupos,id' : 'prohibited',
            'semanas_totales' => 'nullable|integer|min:1|max:52',
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
