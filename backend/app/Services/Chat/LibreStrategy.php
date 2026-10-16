<?php

namespace App\Services\Chat;

use App\Models\ConversacionIa;
use App\Models\Guia;
use App\Models\GuiaTema;
use App\Models\MensajeIa;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


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
        return "Eres un asistente academico del SENA en modo LIBRE, sin restriccion de tema.
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
