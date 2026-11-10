<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferredModeRequest extends FormRequest
{
    /**
     * Permitir que el usuario autenticado cambie su preferencia de interfaz.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validar los modos de interfaz disponibles para estudiantes.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'modo_preferido' => 'required|in:grupo,personal',
        ];
    }
}
