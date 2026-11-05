<?php

namespace App\Services;

use App\Exceptions\GuiaNoProcesableException;
use App\Exceptions\RespuestaIaInvalidaException;
use App\Exceptions\SemanasConEntregasException;
use App\Models\Guia;
use App\Models\GuiaSemana;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Convierte un PDF en una guia dividida en semanas. Solo hace una llamada a
 * Gemini (mas un reintento): el contenido de cada semana se genera despues,
 * en su propia peticion, para no pasar el tiempo limite.
 */
class ProcesarGuiaService
{
    // Con menos texto se asume un PDF escaneado o casi vacio
    private const MIN_CARACTERES = 100;

    // Un intento normal y un reintento si la respuesta de Gemini no es valida
    private const MAX_INTENTOS = 2;

    private const TIEMPO_MAXIMO_SEGUNDOS = 180;

    /**
     * Recibe el extractor de texto por inyeccion de dependencias.
     */
    public function __construct(private PdfTextExtractor $extractor)
    {
    }

    /**
     * Crea una guia desde un PDF. Si el mismo dueño ya subio ese archivo,
     * devuelve la guia existente sin llamar a Gemini.
     *
     * @return array{guia: Guia, creada: bool}
     */
    public function crear(array $datos, UploadedFile $archivo, ?int $semanasSugeridas = null): array
    {
        $hash = hash_file('sha256', $archivo->getRealPath());

        $existente = $this->buscarDuplicada($datos, $hash);
        if ($existente) {
            return ['guia' => $existente, 'creada' => false];
        }

        // El archivo se guarda solo cuando el PDF ya se pudo procesar
        $semanas = $this->analizarPdf($archivo, $semanasSugeridas);
        $ruta = $archivo->store('guias', 'public');

        try {
            $guia = DB::transaction(function () use ($datos, $ruta, $hash, $semanas) {
                $guia = Guia::create($datos + [
                    'archivo_pdf'     => $ruta,
                    'hash_archivo'    => $hash,
                    'semanas_totales' => count($semanas),
                ]);

                $this->sincronizarSemanas($guia, $semanas);

                return $guia;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($ruta);
            throw $e;
        }

        return ['guia' => $guia, 'creada' => true];
    }

    /**
     * Reemplaza el PDF de una guia. Solo las semanas cuyo texto cambio vuelven
     * a pendiente; las demas conservan su contenido.
     *
     * @return array{guia: Guia, semanas_cambiadas: int}
     */
    public function reemplazar(Guia $guia, UploadedFile $archivo, bool $confirmarEliminacion = false): array
    {
        $hash = hash_file('sha256', $archivo->getRealPath());

        if ($hash === $guia->hash_archivo) {
            return ['guia' => $guia, 'semanas_cambiadas' => 0];
        }

        $semanas = $this->analizarPdf($archivo, null);

        if (! $confirmarEliminacion) {
            $this->verificarSemanasConEntregas($guia, count($semanas));
        }

        $rutaAnterior = $guia->archivo_pdf;
        $ruta = $archivo->store('guias', 'public');

        try {
            $cambiadas = DB::transaction(function () use ($guia, $ruta, $hash, $semanas) {
                $guia->update([
                    'archivo_pdf'     => $ruta,
                    'hash_archivo'    => $hash,
                    'semanas_totales' => count($semanas),
                ]);

                return $this->sincronizarSemanas($guia, $semanas);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($ruta);
            throw $e;
        }

        if ($rutaAnterior) {
            Storage::disk('public')->delete($rutaAnterior);
        }

        return ['guia' => $guia, 'semanas_cambiadas' => $cambiadas];
    }

    /**
     * Busca una guia del mismo dueño (grupo o estudiante) con el mismo archivo.
     */
    private function buscarDuplicada(array $datos, string $hash): ?Guia
    {
        $columnaDueno = $datos['origen'] === 'grupo' ? 'grupo_id' : 'usuario_id';

        return Guia::where('hash_archivo', $hash)
            ->where($columnaDueno, $datos[$columnaDueno])
            ->first();
    }

    /**
     * Extrae el texto del PDF y lo divide en semanas con Gemini.
     * Si la respuesta no es valida, o sus frases no aparecen en el PDF, reintenta una vez.
     */
    private function analizarPdf(UploadedFile $archivo, ?int $semanasSugeridas): array
    {
        $texto = $this->leerTexto($archivo);

        // Gemini puede tardar mas que el limite por defecto de PHP
        set_time_limit(self::TIEMPO_MAXIMO_SEGUNDOS);

        $gemini = GeminiService::obtenerInstancia();

        for ($intento = 1; ; $intento++) {
            try {
                return $this->cortarTexto($texto, $gemini->dividirEnSemanas($texto, $semanasSugeridas));
            } catch (RespuestaIaInvalidaException $e) {
                if ($intento >= self::MAX_INTENTOS) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Lee el PDF y rechaza los que no tienen texto o superan el limite configurado.
     */
    private function leerTexto(UploadedFile $archivo): string
    {
        $texto = mb_scrub((string) $this->extractor->extraer($archivo->getRealPath()), 'UTF-8');

        if (mb_strlen(trim($texto)) < self::MIN_CARACTERES) {
            throw new GuiaNoProcesableException(
                'El PDF no tiene texto legible. Sube un PDF que no sea una imagen escaneada.'
            );
        }

        if (mb_strlen($texto) > (int) config('services.gemini.max_caracteres_guia')) {
            throw new GuiaNoProcesableException(
                'La guía es demasiado larga para procesarla. Divídela en dos PDF.'
            );
        }

        return $texto;
    }

    /**
     * Corta el texto real del PDF usando la frase de inicio de cada semana.
     * El hash se calcula sobre este texto, no sobre algo redactado por Gemini:
     * asi, si el PDF no cambia, el hash tampoco cambia.
     */
    private function cortarTexto(string $texto, array $semanas): array
    {
        $inicios = [];
        $desde = 0;

        foreach ($semanas as $semana) {
            [$inicio, $desde] = $this->buscarFrase($texto, $semana['inicio_texto'], $desde);
            $inicios[] = $inicio;
        }

        // Lo que esta antes de la primera frase (portada, objetivos) queda en la semana 1
        $inicios[0] = 0;

        foreach ($semanas as $indice => &$semana) {
            $fin = $inicios[$indice + 1] ?? strlen($texto);
            $semana['contenido_texto'] = trim(substr($texto, $inicios[$indice], $fin - $inicios[$indice]));
            $semana['hash_contenido'] = hash('sha256', $semana['contenido_texto']);
            unset($semana['inicio_texto']);
        }

        return $semanas;
    }

    /**
     * Busca una frase desde una posicion, sin distinguir mayusculas ni espacios,
     * porque el texto extraido de un PDF viene irregular.
     *
     * @return array{0: int, 1: int} Posicion donde empieza y donde termina la frase.
     */
    private function buscarFrase(string $texto, string $frase, int $desde): array
    {
        $palabras = preg_split('/\s+/u', trim($frase), -1, PREG_SPLIT_NO_EMPTY);
        $patron = '/' . implode('\s*', array_map(fn ($p) => preg_quote($p, '/'), $palabras)) . '/iu';

        if (preg_match($patron, $texto, $coincidencia, PREG_OFFSET_CAPTURE, $desde) !== 1) {
            throw new RespuestaIaInvalidaException('Una frase de inicio no aparece en el PDF.');
        }

        [$encontrado, $inicio] = $coincidencia[0];

        return [$inicio, $inicio + strlen($encontrado)];
    }

    /**
     * Impide borrar sin confirmacion semanas que ya tienen entregas.
     */
    private function verificarSemanasConEntregas(Guia $guia, int $totalNuevo): void
    {
        $afectadas = $guia->semanas()
            ->where('numero_semana', '>', $totalNuevo)
            ->whereHas('miniProyectos.entregas')
            ->pluck('numero_semana')
            ->all();

        if ($afectadas !== []) {
            throw new SemanasConEntregasException($afectadas);
        }
    }

    /**
     * Crea o actualiza las semanas y sus temas, y borra las que ya no existen.
     * Se puede repetir sin duplicar datos. Retorna cuantas semanas son nuevas o cambiaron.
     */
    private function sincronizarSemanas(Guia $guia, array $semanas): int
    {
        // Los temas no tienen nada que dependa de ellos: se reemplazan completos
        $guia->temas()->delete();

        $cambiadas = 0;
        $ordenTema = 1;

        foreach ($semanas as $datos) {
            $semana = GuiaSemana::firstOrNew([
                'guia_id'       => $guia->id,
                'numero_semana' => $datos['numero_semana'],
            ]);

            $semana->fill(['titulo' => $datos['titulo'], 'resumen' => $datos['resumen']]);

            if ($semana->hash_contenido !== $datos['hash_contenido']) {
                $semana->fill([
                    'contenido_texto'   => $datos['contenido_texto'],
                    'hash_contenido'    => $datos['hash_contenido'],
                    'estado_generacion' => 'pendiente',
                    'error_mensaje'     => null,
                ]);
                $cambiadas++;
            }

            $semana->save();

            foreach ($datos['temas'] as $tema) {
                $semana->temas()->create([
                    'guia_id'     => $guia->id,
                    'nombre_tema' => $tema['nombre_tema'],
                    'descripcion' => $tema['descripcion'],
                    'orden'       => $ordenTema++, // Orden en toda la guia: lo usa el chat guiado
                ]);
            }
        }

        $guia->semanas()->where('numero_semana', '>', count($semanas))->delete();

        return $cambiadas;
    }
}
