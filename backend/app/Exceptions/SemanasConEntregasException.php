<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Reemplazar el PDF borraria semanas que ya tienen entregas de estudiantes.
 * Hay que pedir confirmacion antes de continuar.
 */
class SemanasConEntregasException extends RuntimeException
{
    /**
     * Guarda los numeros de las semanas que se perderian.
     */
    public function __construct(public readonly array $semanas)
    {
        parent::__construct('El nuevo PDF elimina semanas que ya tienen entregas de estudiantes.');
    }
}
