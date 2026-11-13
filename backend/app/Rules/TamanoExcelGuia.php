<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Un Excel ocupa mucha más memoria al leerse que su peso en disco, así que tiene un límite
 * menor que el PDF (10 MB). Un pacto pedagógico normal pesa menos de 1 MB.
 */
class TamanoExcelGuia implements ValidationRule
{
    /**
     * Falla solo si el archivo es un Excel y pesa más que GUIA_EXCEL_MAX_KB.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || $value->guessExtension() !== 'xlsx') {
            return;
        }

        $maximoKb = (int) config('services.guia_excel.max_kb');
        if ($value->getSize() > $maximoKb * 1024) {
            // 2048 KB se muestra como "2 MB"; un límite menor a 1 MB, en KB
            $limite = $maximoKb >= 1024 ? round($maximoKb / 1024, 1) . ' MB' : "{$maximoKb} KB";
            $fail("El Excel no puede pesar más de {$limite}.");
        }
    }
}
