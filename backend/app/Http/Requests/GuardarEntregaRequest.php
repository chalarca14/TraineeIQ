<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarEntregaRequest extends FormRequest
{
    // Solo https://github.com/{dueño}/{repo}, sin subrutas, parametros ni .git al final.
    // La D evita que un salto de linea final pase la validacion
    private const REPO_GITHUB = '#^https://github\.com/[A-Za-z0-9][A-Za-z0-9-]{0,38}/(?!\.{1,2}$)[A-Za-z0-9._-]{1,100}(?<!\.git)$#D';

    /**
     * La autorizacion sobre el mini proyecto la resuelve su Policy en el controlador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * La regla va en un arreglo porque el patron podria contener el caracter |.
     */
    public function rules(): array
    {
        return [
            'repo_url' => ['required', 'string', 'max:255', 'regex:' . self::REPO_GITHUB],
        ];
    }

    /**
     * Mensajes para el estudiante.
     */
    public function messages(): array
    {
        return [
            'repo_url.required' => 'Escribe el link de tu repositorio.',
            'repo_url.regex'    => 'El link debe tener la forma https://github.com/usuario/repositorio.',
        ];
    }
}
