<?php

namespace App\Services\Chat;

use App\Models\ConversacionIa;
use App\Models\Guia;
use App\Models\GuiaTema;
use App\Models\MensajeIa;
use App\Services\GeminiService;
use Illuminate\Support\Collection;

/**
 * Estrategia para el modo GUIADO: la conversacion queda condicionada al
 * contenido de UNA guia especifica (conversacion->guia_id): su nombre, sus
 * semanas y sus temas. Si la pregunta se sale de ese alcance, se rechaza y se
 * sugiere el modo libre.
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

    /**
     * Arma el prompt con el contenido de la guia, pide la respuesta a Gemini y la
     * valida en dos capas antes de devolverla.
     */
    public function generarRespuesta(ConversacionIa $conversacion, string $mensajeUsuario): string
    {
        $guia = Guia::find($conversacion->guia_id);
        $semanas = $guia ? $guia->semanas()->get(['numero_semana', 'titulo', 'resumen']) : collect();
        $temas = GuiaTema::where('guia_id', $conversacion->guia_id)
            ->orderBy('orden')
            ->get();

        $contenidoGuia = $this->formatearContenidoGuia($guia, $semanas, $temas);
        $historial = $this->formatearHistorial($conversacion);
        $prompt = $this->construirPrompt($contenidoGuia, $historial, $mensajeUsuario);

        $respuesta = $this->geminiService->generarRespuestaChat($prompt);

        // Capa 1: el propio modelo se autoevalua con el marcador que le pedimos en el prompt.
        if (str_starts_with(trim($respuesta), self::MARCADOR_FUERA_DE_ALCANCE)) {
            return self::MENSAJE_REDIRECCION;
        }

        // Capa 2: validacion real de backend, no solo confiar en el marcador del modelo.
        // Si la respuesta no comparte ninguna palabra significativa con la guia, se trata
        // como sospechosa y se redirige igual, aunque el modelo no haya usado el marcador.
        if ($this->pareceFueraDeAlcance($respuesta, $guia, $semanas, $temas)) {
            return self::MENSAJE_REDIRECCION;
        }

        return $respuesta;
    }

    /**
     * Junta en texto plano lo que la IA puede usar: el nombre de la guia, sus semanas
     * (titulo y resumen) y sus temas. Antes solo iban los temas, y la IA no sabia ni
     * como se llamaba la guia, asi que "¿de que trata la guia?" le parecia ajeno.
     */
    private function formatearContenidoGuia(?Guia $guia, Collection $semanas, Collection $temas): string
    {
        $partes = ['Nombre de la guia: ' . ($guia?->nombre ?? 'sin nombre')];

        if ($semanas->isNotEmpty()) {
            $partes[] = "Semanas de la guia:\n" . $semanas
                ->map(fn ($s) => "- Semana {$s->numero_semana}: {$s->titulo}. {$s->resumen}")
                ->implode("\n");
        }

        if ($temas->isNotEmpty()) {
            $partes[] = "Temas de la guia:\n" . $temas
                ->map(fn ($t) => "- {$t->nombre_tema}: {$t->descripcion}")
                ->implode("\n");
        }

        return implode("\n\n", $partes);
    }

    /**
     * Arma el prompt restringido: incluye SOLO el contenido de esta guia y le exige
     * al modelo usar el marcador si la pregunta no corresponde a ese contenido. Las
     * preguntas sobre la guia misma (de que trata, que se aprende, que semanas tiene)
     * se declaran validas de forma explicita, porque el modelo las rechazaba.
     */
    private function construirPrompt(string $contenidoGuia, string $historial, string $mensajeUsuario): string
    {
        return "Eres el asistente academico de TraineeIQ en modo GUIADO.
        " . self::CONTEXTO_TRAINEEIQ . "
        Tu conocimiento en esta conversacion esta limitado UNICAMENTE al siguiente contenido de la guia:
        {$contenidoGuia}

        Historial reciente de la conversacion:
        {$historial}

        Las preguntas sobre la guia misma SIEMPRE tienen relacion con el contenido: de que trata,
        cual es su objetivo, que se va a aprender, que temas o semanas tiene, o un resumen.
        Respondelas explicando la guia con su nombre, sus semanas y sus temas. No hables de la
        plataforma TraineeIQ salvo que el estudiante pregunte por ella.

        Si la pregunta del estudiante NO tiene relacion con el contenido anterior, responde
        EXACTAMENTE con: \"" . self::MARCADOR_FUERA_DE_ALCANCE . "\" seguido de una razon breve.
        Si SI tiene relacion, respondela de forma clara y pedagogica, sin salirte del contenido.

        Pregunta del estudiante: {$mensajeUsuario}";
    }

    /**
     * Verificacion de backend (capa 2): compara las palabras clave de la respuesta con
     * las de la guia (nombre, semanas y temas con su descripcion). No es un analisis
     * semantico real, pero es una validacion en PHP y no depende de que el modelo
     * obedezca el marcador (cumple RNF-17).
     * Antes solo miraba los nombres de los temas y partia las palabras con tilde, asi que
     * podia tumbar una respuesta buena. Si la guia no tiene semanas ni temas, no hay contra
     * que comparar y se rechaza, como antes.
     */
    private function pareceFueraDeAlcance(string $respuesta, ?Guia $guia, Collection $semanas, Collection $temas): bool
    {
        if ($semanas->isEmpty() && $temas->isEmpty()) {
            return true;
        }

        $textoGuia = collect([$guia?->nombre])
            ->merge($semanas->map(fn ($s) => "{$s->titulo} {$s->resumen}"))
            ->merge($temas->map(fn ($t) => "{$t->nombre_tema} {$t->descripcion}"))
            ->implode(' ');

        $interseccion = array_intersect(PalabrasClave::extraer($textoGuia), PalabrasClave::extraer($respuesta));

        return count($interseccion) === 0;
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
