<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * El repositorio no se puede cambiar mientras la entrega se esta evaluando.
 */
class EntregaEnEvaluacionException extends RuntimeException
{
    /**
     * Fija el mensaje que se le muestra al estudiante.
     */
    public function __construct()
    {
        parent::__construct('Tu entrega se está evaluando. Espera el resultado para cambiar el repositorio.');
    }
}
