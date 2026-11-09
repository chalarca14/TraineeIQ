<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * No se pudo leer el repositorio. El estado indica de quien es el problema:
 * 422 si el estudiante debe corregir algo, 503 si GitHub fallo y se puede reintentar.
 */
class RepositorioNoDisponibleException extends RuntimeException
{
    /**
     * Guarda el mensaje para el estudiante y el codigo HTTP de la respuesta.
     */
    public function __construct(string $mensaje, public readonly int $estadoHttp = 422)
    {
        parent::__construct($mensaje);
    }
}
