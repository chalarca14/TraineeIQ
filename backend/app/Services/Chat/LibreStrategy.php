<?php

namespace App\Services\Chat;

use App\Models\ConversacionIa;
use App\Models\Guia;
use App\Models\GuiaTema;
use App\Models\MensajeIa;
use App\Services\GeminiService;

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

        $temaRelacionado = $this->buscarTemaRelacionado($conversacion->estudiante_id, $mensajeUsuario);

        if ($temaRelacionado) {
            $respuesta .= "\n\n(Esto tambien lo puedes profundizar en tu guia \"{$temaRelacionado->guia->nombre}\", tema \"{$temaRelacionado->nombre_tema}\".)";
        }

        return $respuesta;
    }

    private function construirPrompt(string $historial, string $mensajeUsuario): string
    {
        return "Eres un asistente academico del SENA en modo LIBRE, sin restriccion de tema.
        Responde de forma clara y pedagogica a preguntas de programacion en general.

        Historial reciente de la conversacion:
        {$historial}

        Pregunta del estudiante: {$mensajeUsuario}";
    }

    /**
     * Busca, entre las guias del estudiante (personales y de sus grupos), un
     * tema cuyas palabras clave coincidan con la pregunta. Retorna el primer
     * match o null si no hay ninguno.
     */
    private function buscarTemaRelacionado(int $estudianteId, string $mensajeUsuario): ?GuiaTema
    {
        $palabrasPregunta = $this->extraerPalabrasClave($mensajeUsuario);

        if (empty($palabrasPregunta)) {
            return null;
        }

        $idsGuiasDelEstudiante = Guia::where('usuario_id', $estudianteId)
            ->orWhereHas('grupo.estudiantes', fn ($q) => $q->where('users.id', $estudianteId))
            ->pluck('id');

        $temas = GuiaTema::with('guia')
            ->whereIn('guia_id', $idsGuiasDelEstudiante)
            ->get();

        foreach ($temas as $tema) {
            $palabrasTema = $this->extraerPalabrasClave($tema->nombre_tema);
            if (count(array_intersect($palabrasPregunta, $palabrasTema)) > 0) {
                return $tema;
            }
        }

        return null;
    }

    private function extraerPalabrasClave(string $texto): array
    {
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9\s]/', ' ', $texto);
        $palabras = array_filter(explode(' ', $texto), fn ($p) => strlen($p) > 3);

        return array_values(array_unique($palabras));
    }

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