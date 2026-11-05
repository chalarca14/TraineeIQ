<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Gemini no respondio: esta saturada, sin cuota o sin conexion.
 */
class IaNoDisponibleException extends RuntimeException
{
}
