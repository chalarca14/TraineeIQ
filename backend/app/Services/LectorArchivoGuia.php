<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Saca el texto de un archivo de guía (PDF o Excel). Lo usan la subida de guías
 * (ProcesarGuiaService) y las guías de la semana (MaterialSemanaService).
 */
class LectorArchivoGuia
{
    /**
     * Recibe los dos lectores por inyección de dependencias (así las pruebas pueden simularlos).
     */
    public function __construct(
        private PdfTextExtractor $extractorPdf,
        private ExcelTextExtractor $extractorExcel,
    ) {
    }

    /**
     * Devuelve el texto del archivo y si es un Excel. El tipo se decide por el contenido del
     * archivo (guessExtension), no por su nombre. El texto queda en UTF-8 válido.
     *
     * @return array{texto: string, esExcel: bool}
     */
    public function leer(UploadedFile $archivo): array
    {
        $esExcel = $archivo->guessExtension() === 'xlsx';
        $lector = $esExcel ? $this->extractorExcel : $this->extractorPdf;

        return [
            'texto'   => mb_scrub((string) $lector->extraer($archivo->getRealPath()), 'UTF-8'),
            'esExcel' => $esExcel,
        ];
    }
}
