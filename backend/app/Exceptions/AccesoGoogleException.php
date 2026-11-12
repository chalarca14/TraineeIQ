<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * No se pudo entrar o registrarse con Google. Lleva el mensaje en español
 * y el código HTTP que debe responder el controlador.
 */
class AccesoGoogleException extends RuntimeException
{
    /**
     * Guarda el mensaje para el usuario y el código HTTP de la respuesta.
     */
    public function __construct(string $mensaje, public readonly int $estado)
    {
        parent::__construct($mensaje);
    }
}
