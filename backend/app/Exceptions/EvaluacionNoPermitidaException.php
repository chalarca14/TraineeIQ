<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * La evaluacion no se puede iniciar: no hay repositorio, ya hay una en curso
 * o se alcanzo el tope diario.
 */
class EvaluacionNoPermitidaException extends RuntimeException
{
    /**
     * Guarda el mensaje para el estudiante y el codigo HTTP de la respuesta.
     */
    public function __construct(string $mensaje, public readonly int $estadoHttp)
    {
        parent::__construct($mensaje);
    }
}
