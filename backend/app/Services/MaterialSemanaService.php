<?php

namespace App\Services;

use App\Exceptions\GuiaNoProcesableException;
use App\Models\GuiaSemana;
use App\Models\GuiaSemanaMaterial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Guías de la semana: las guías detalladas que el instructor adjunta a una semana de la
 * guía principal. Solo se guarda su texto. No llama a Gemini: la IA las usa cuando la
 * semana se prepara (al liberarla) o cuando el instructor pide "Actualizar".
 */
class MaterialSemanaService
{
    // Máximo de guías por semana
    public const MAX_POR_SEMANA = 5;

    // Con menos texto se asume un PDF escaneado o un Excel vacío
    private const MIN_CARACTERES = 100;

    // Tope de texto de TODAS las guías de una semana: todo va en una sola llamada a Gemini
    private const MAX_CARACTERES_SEMANA = 60000;

    /**
     * Recibe el lector de archivos y el servicio de avisos por inyección de dependencias.
     */
    public function __construct(
        private LectorArchivoGuia $lector,
        private NotificacionService $notificaciones,
    ) {
    }

    /**
     * Adjunta una guía a la semana. Rechaza (GuiaNoProcesableException, con mensaje para el
     * usuario) si ya hay 5, si el archivo está repetido, si no tiene texto o si la semana
     * superaría el tope de texto. Si la semana ya está liberada, avisa a los aprendices.
     *
     * @throws GuiaNoProcesableException
     */
    public function agregar(GuiaSemana $semana, string $nombre, UploadedFile $archivo): GuiaSemanaMaterial
    {
        if ($semana->materiales()->count() >= self::MAX_POR_SEMANA) {
            throw new GuiaNoProcesableException('Esta semana ya tiene ' . self::MAX_POR_SEMANA . ' guías. Quita una para agregar otra.');
        }

        $hash = hash_file('sha256', $archivo->getRealPath());
        if ($semana->materiales()->where('hash_archivo', $hash)->exists()) {
            throw new GuiaNoProcesableException('Ese archivo ya está adjunto a esta semana.');
        }

        ['texto' => $texto, 'esExcel' => $esExcel] = $this->lector->leer($archivo);
        $texto = trim($texto);

        if (mb_strlen($texto) < self::MIN_CARACTERES) {
            throw new GuiaNoProcesableException($esExcel
                ? 'El Excel no tiene texto suficiente. Revisa que la guía no esté en una hoja oculta.'
                : 'El PDF no tiene texto legible. Sube un PDF que no sea una imagen escaneada.');
        }

        $yaUsado = (int) $semana->materiales()->get(['contenido_texto'])->sum(fn ($m) => mb_strlen($m->contenido_texto));
        if ($yaUsado + mb_strlen($texto) > self::MAX_CARACTERES_SEMANA) {
            throw new GuiaNoProcesableException('Las guías de esta semana serían demasiado largas para la IA. Sube una versión más corta o quita otra guía.');
        }

        $material = $semana->materiales()->create([
            'nombre'          => $nombre,
            'contenido_texto' => $texto,
            'hash_archivo'    => $hash,
        ]);

        // Un aviso que falla no debe deshacer la subida
        try {
            $this->notificaciones->avisarMaterialNuevo($semana->guia, $semana->numero_semana, $nombre);
        } catch (\Throwable $e) {
            Log::error('No se pudo avisar de la guía de la semana', ['guia_semana_id' => $semana->id, 'mensaje' => $e->getMessage()]);
        }

        return $material;
    }

    /**
     * Quita una guía de la semana. Lo ya generado con ella no cambia hasta que el instructor
     * pida "Actualizar".
     */
    public function quitar(GuiaSemanaMaterial $material): void
    {
        $material->delete();
    }
}
