<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Gemini respondio, pero su respuesta no cumple el contrato JSON.
 * A diferencia de IaNoDisponibleException, vale la pena reintentar.
 */
class RespuestaIaInvalidaException extends RuntimeException
{
}
