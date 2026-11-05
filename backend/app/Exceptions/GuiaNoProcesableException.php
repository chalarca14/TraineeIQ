<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * El PDF no se puede procesar: no tiene texto legible o es demasiado largo.
 * El mensaje esta pensado para mostrarselo al usuario.
 */
class GuiaNoProcesableException extends RuntimeException
{
}
