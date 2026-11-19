<?php

namespace App\Services\Chat;

use App\Models\ConversacionIa;
use App\Models\Guia;
use App\Models\GuiaTema;
use App\Models\MensajeIa;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;


/**
 * Estrategia para el modo LIBRE: sin restriccion de contenido. Ademas,
 * compara la pregunta contra los temas de TODAS las guias del estudiante
 * (personales y de sus grupos) para sugerirle donde profundizar.
 */
class LibreStrategy implements ChatStrategy
{
    private GeminiService $geminiService;

    public function __construct()
    {
        $this->geminiService = GeminiService::obtenerInstancia();
    }

    public function generarRespuesta(ConversacionIa $conversacion, string $mensajeUsuario): string
    {
        $historial = $this->formatearHistorial($conversacion);
        $prompt = $this->construirPrompt($historial, $mensajeUsuario);

        $respuesta = $this->geminiService->generarRespuestaChat($prompt);

        // La sugerencia de temas es opcional: nunca debe tumbar la respuesta de Gemini.
        try {
            $temaRelacionado = $this->buscarTemaRelacionado($conversacion->estudiante_id, $mensajeUsuario);
        } catch (\Throwable $e) {
            Log::warning('LibreStrategy: no se pudo buscar tema relacionado', [
                'mensaje' => $e->getMessage(),
            ]);
            $temaRelacionado = null;
        }

        if ($temaRelacionado) {
            $respuesta .= "\n\n(Esto tambien lo puedes profundizar en tu guia \"{$temaRelacionado->guia->nombre}\", tema \"{$temaRelacionado->nombre_tema}\".)";
        }

        return $respuesta;
    }

    private function construirPrompt(string $historial, string $mensajeUsuario): string
    {
        return "Eres el asistente academico de TraineeIQ en modo LIBRE, sin restriccion de tema.
        " . self::CONTEXTO_TRAINEEIQ . "
        Responde de forma clara y pedagogica a preguntas de programacion en general.

        Historial reciente de la conversacion:
        {$historial}

        Pregunta del estudiante: {$mensajeUsuario}";
    }

    /**
     * Busca, entre las guias del estudiante (personales y de sus grupos), un
     * tema cuyas palabras clave coincidan con la pregunta. Retorna el primer
     * match o null si no hay ninguno / el esquema aun no soporta temas.
     */
    private function buscarTemaRelacionado(int $estudianteId, string $mensajeUsuario): ?GuiaTema
    {
        if (! Schema::hasTable('guia_temas')) {
            return null;
        }

        $palabrasPregunta = $this->extraerPalabrasClave($mensajeUsuario);

        if (empty($palabrasPregunta)) {
            return null;
        }

        $query = Guia::query();

        if (Schema::hasColumn('guias', 'usuario_id')) {
            $query->where(function ($q) use ($estudianteId) {
                $q->where('usuario_id', $estudianteId)
                    ->orWhereHas('grupo.estudiantes', fn ($sub) => $sub->where('users.id', $estudianteId));
            });
        } else {
            // Esquema actual: solo guias de grupos a los que pertenece el estudiante.
            $query->whereHas('grupo.estudiantes', fn ($q) => $q->where('users.id', $estudianteId));
        }

        $idsGuiasDelEstudiante = $query->pluck('id');

        if ($idsGuiasDelEstudiante->isEmpty()) {
            return null;
        }

        $temas = GuiaTema::with('guia')
            ->whereIn('guia_id', $idsGuiasDelEstudiante)
            ->get();

        // Se elige el tema que comparte MÁS palabras clave con la pregunta, no el primero que
        // comparta alguna. Si empatan, gana el que aparece primero (orden de la base)
        $mejorTema = null;
        $mejorCoincidencias = 0;

        foreach ($temas as $tema) {
            $palabrasTema = $this->extraerPalabrasClave($tema->nombre_tema);
            $coincidencias = count(array_intersect($palabrasPregunta, $palabrasTema));

            if ($coincidencias > $mejorCoincidencias) {
                $mejorTema = $tema;
                $mejorCoincidencias = $coincidencias;
            }
        }

        return $mejorTema;
    }

    /**
     * Saca las palabras que de verdad dicen de qué trata un texto, para comparar la pregunta
     * con los nombres de los temas:
     * - pasa a minúsculas y quita las tildes ("básicas" → "basicas"); antes las tildes se
     *   borraban junto con la letra y la palabra quedaba partida ("b" y "sicas");
     * - acepta palabras desde 3 letras, para que cuenten siglas como "git", "php" o "css";
     * - ignora las palabras comunes (PALABRAS_IGNORADAS), que no dicen nada del tema: antes
     *   "¿Qué es Git y para qué sirve?" se relacionaba con "Estructuras básicas para datos"
     *   solo por la palabra "para".
     */
    private function extraerPalabrasClave(string $texto): array
    {
        $texto = Str::lower(Str::ascii($texto));
        $texto = preg_replace('/[^a-z0-9\s]/', ' ', $texto);

        $palabras = array_filter(
            explode(' ', $texto),
            fn ($p) => strlen($p) >= 3 && ! in_array($p, self::PALABRAS_IGNORADAS, true)
        );

        return array_values(array_unique($palabras));
    }

    /**
     * Palabras frecuentes en las preguntas y en los nombres de los temas que no sirven para
     * saber de qué tema se habla (van sin tildes, porque se comparan ya normalizadas).
     */
    private const PALABRAS_IGNORADAS = [
        // Artículos, preposiciones, conectores y pronombres
        'los', 'las', 'del', 'con', 'por', 'para', 'sin', 'una', 'uno', 'unos', 'unas', 'que',
        'cual', 'cuales', 'como', 'cuando', 'donde', 'entre', 'sobre', 'desde', 'hasta', 'hacia',
        'segun', 'pero', 'porque', 'pues', 'tambien', 'ademas', 'mas', 'muy', 'solo', 'esto',
        'esta', 'este', 'estos', 'estas', 'eso', 'esa', 'ese', 'esos', 'esas', 'aqui', 'algo',
        'todo', 'toda', 'todos', 'todas', 'cada', 'otro', 'otra', 'otros', 'otras', 'mis', 'tus',
        'sus', 'nos', 'les', 'ellos', 'ellas', 'usted', 'ustedes', 'mismo', 'misma', 'bien', 'mal',
        // Verbos y palabras típicas de una pregunta
        'son', 'hay', 'ser', 'estar', 'tiene', 'tienen', 'tengo', 'hace', 'hacer', 'hago', 'puede',
        'pueden', 'puedo', 'quiero', 'necesito', 'sirve', 'sirven', 'servir', 'usar', 'uso',
        'utiliza', 'utilizar', 'funciona', 'funcionan', 'explica', 'explicame', 'explicar', 'dame',
        'dime', 'muestra', 'muestrame', 'ayuda', 'ayudame', 'ejemplo', 'ejemplos', 'diferencia',
        'diferencias', 'significa', 'hola', 'gracias', 'favor', 'porfa', 'sencillo', 'facil',
        // Palabras genéricas de los nombres de temas
        'tema', 'temas', 'guia', 'semana', 'introduccion', 'concepto', 'conceptos', 'basico',
        'basica', 'basicos', 'basicas', 'general', 'generales', 'parte',
    ];

    private function formatearHistorial(ConversacionIa $conversacion): string
    {
        $mensajes = MensajeIa::where('conversacion_id', $conversacion->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse();

        return $mensajes
            ->map(fn ($m) => ($m->emisor === 'usuario' ? 'Estudiante: ' : 'IA: ') . $m->contenido)
            ->implode("\n");
    }
}
