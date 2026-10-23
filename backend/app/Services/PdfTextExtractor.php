<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

/**
 * Extrae el texto de un PDF guardado en disco.
 */
class PdfTextExtractor
{
    /**
     * Retorna el texto limpio del PDF, o null si no se pudo leer o no tiene texto
     * (por ejemplo, un PDF escaneado como imagen).
     */
    public function extraer(string $rutaAbsoluta): ?string
    {
        try {
            $pdf = (new Parser())->parseFile($rutaAbsoluta);
            $texto = trim($pdf->getText());

            // Normaliza espacios repetidos y saltos de linea de mas
            $texto = preg_replace('/[ \t]+/', ' ', $texto);
            $texto = preg_replace('/\n{3,}/', "\n\n", $texto);

            return ($texto !== null && $texto !== '') ? $texto : null;
        } catch (\Exception $e) {
            Log::error('No se pudo leer el PDF', ['mensaje' => $e->getMessage()]);
            return null;
        }
    }
}