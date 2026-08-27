<?php

namespace App\Services\Chat;

use App\Models\ConversacionIa;
use App\Models\GuiaTema;
use App\Models\MensajeIa;
use App\Services\GeminiService;

/**
 * Estrategia para el modo GUIADO: la conversacion queda condicionada al
 * contenido de guia_temas de UNA guia especifica (conversacion->guia_id).
 * Si la pregunta se sale de ese alcance, se rechaza y se sugiere el modo libre.
 */
class GuiadaStrategy implements ChatStrategy
{
    private GeminiService $geminiService;

    private const MARCADOR_FUERA_DE_ALCANCE = 'FUERA_DE_ALCANCE:';
    private const MENSAJE_REDIRECCION = 'Ese tema se sale de esta guia. Lo puedes explorar en Conversar con la IA (modo libre).';

    public function __construct()
    {
        $this->geminiService = GeminiService::obtenerInstancia();
    }

    public function generarRespuesta(ConversacionIa $conversacion, string $mensajeUsuario): string
    {
        $temas = GuiaTema::where('guia_id', $conversacion->guia_id)
            ->orderBy('orden')
            ->get();

        $contenidoGuia = $temas
            ->map(fn ($t) => "- {$t->nombre_tema}: {$t->descripcion}")
            ->implode("\n");

        $historial = $this->formatearHistorial($conversacion);
        $prompt = $this->construirPrompt($contenidoGuia, $historial, $mensajeUsuario);

        $respuesta = $this->geminiService->generarRespuestaChat($prompt);

        // Capa 1: el propio modelo se autoevalua con el marcador que le pedimos en el prompt.
        if (str_starts_with(trim($respuesta), self::MARCADOR_FUERA_DE_ALCANCE)) {
            return self::MENSAJE_REDIRECCION;
        }

        // Capa 2: validacion real de backend, no solo confiar en el marcador del modelo.
        // Si la respuesta no comparte ninguna palabra significativa con los temas de la
        // guia, se trata como sospechosa y se redirige igual, aunque el modelo no haya
        // usado el marcador.
        if ($this->pareceFueraDeAlcance($respuesta, $temas)) {
            return self::MENSAJE_REDIRECCION;
        }

        return $respuesta;
    }

    /**
     * Arma el prompt restringido: incluye SOLO el contenido de los temas de
     * esta guia y le exige al modelo usar el marcador si la pregunta no
     * corresponde a ese contenido.
     */
    private function construirPrompt(string $contenidoGuia, string $historial, string $mensajeUsuario): string
    {
        return "Eres un asistente academico del SENA en modo GUIADO.
        Tu conocimiento en esta conversacion esta limitado UNICAMENTE al siguiente contenido:
        {$contenidoGuia}

        Historial reciente de la conversacion:
        {$historial}

        Si la pregunta del estudiante NO tiene relacion con el contenido anterior, responde
        EXACTAMENTE con: \"" . self::MARCADOR_FUERA_DE_ALCANCE . "\" seguido de una razon breve.
        Si SI tiene relacion, respondela de forma clara y pedagogica, sin salirte del contenido.

        Pregunta del estudiante: {$mensajeUsuario}";
    }

    /**
     * Verificacion de backend (capa 2): compara palabras significativas de la
     * respuesta contra las palabras de los temas de la guia. No es un analisis
     * semantico real, pero es una validacion en PHP y no depende de que el
     * modelo obedezca el marcador (cumple RNF-17).
     */
    private function pareceFueraDeAlcance(string $respuesta, $temas): bool
    {
        if ($temas->isEmpty()) {
            return true;
        }

        $palabrasTemas = $this->extraerPalabrasClave($temas->pluck('nombre_tema')->implode(' '));
        $palabrasRespuesta = $this->extraerPalabrasClave($respuesta);

        $interseccion = array_intersect($palabrasTemas, $palabrasRespuesta);

        return count($interseccion) === 0;
    }

    /**
     * Extrae palabras de mas de 3 letras, en minuscula, sin acentos ni
     * puntuacion, para comparar contenido de forma tolerante.
     */
    private function extraerPalabrasClave(string $texto): array
    {
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9\s]/', ' ', $texto);
        $palabras = array_filter(explode(' ', $texto), fn ($p) => strlen($p) > 3);

        return array_values(array_unique($palabras));
    }

    /**
     * Convierte los ultimos mensajes de la conversacion en texto plano,
     * para darle memoria a Gemini sin reenviar toda la tabla.
     */
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