<?php

namespace Tests\Feature;

use App\Services\GeminiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Cómo se lee la respuesta de Gemini: el texto puede llegar partido en varias partes.
 */
class GeminiRespuestaTest extends TestCase
{
    /**
     * Borra la instancia del Singleton para que otras pruebas no hereden los modelos de aquí.
     */
    protected function tearDown(): void
    {
        (new \ReflectionProperty(GeminiService::class, 'instancia'))->setValue(null, null);
        parent::tearDown();
    }

    /**
     * Una respuesta del chat partida en dos partes llega completa, no solo la primera mitad.
     * Las partes de "pensamiento" del modelo no se muestran.
     */
    public function test_junta_todas_las_partes_de_texto_y_salta_el_pensamiento(): void
    {
        Http::fake(['*' => Http::response(['candidates' => [[
            'finishReason' => 'STOP',
            'content'      => ['parts' => [
                ['text' => 'Pensando cómo responder…', 'thought' => true],
                ['text' => 'La IA toma cada guía, la divide por '],
                ['text' => 'semanas y prepara sus mini proyectos.', 'thoughtSignature' => 'firma'],
            ]],
        ]]])]);

        $respuesta = GeminiService::obtenerInstancia()->generarRespuestaChat('¿Cómo funciona la plataforma?');

        $this->assertSame('La IA toma cada guía, la divide por semanas y prepara sus mini proyectos.', $respuesta);
    }

    /**
     * El chat pide pensamiento bajo (responde más rápido) y anota en el log los tokens que gastó.
     */
    public function test_el_chat_piensa_poco_y_anota_los_tokens(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response([
            'candidates'    => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => 'Hola']]]]],
            'usageMetadata' => ['promptTokenCount' => 120, 'candidatesTokenCount' => 30, 'thoughtsTokenCount' => 0, 'totalTokenCount' => 150],
        ])]);

        GeminiService::obtenerInstancia()->generarRespuestaChat('Hola');

        Http::assertSent(fn ($peticion) => ($peticion['generationConfig']['thinkingConfig']['thinkingLevel'] ?? null) === 'low');
        Log::shouldHaveReceived('info')
            ->withArgs(fn ($mensaje, $datos) => $mensaje === 'Gemini uso de tokens'
                && $datos['entrada'] === 120 && $datos['salida'] === 30 && $datos['pensamiento'] === 0)
            ->once();
    }

    /**
     * Con Flash Lite de principal, la evaluación de código va primero al modelo de evaluación
     * (3.6 Flash), mientras el chat sigue usando el principal.
     */
    public function test_la_evaluacion_usa_primero_su_propio_modelo(): void
    {
        $this->modelos('gemini-3.5-flash-lite', 'gemini-3.6-flash', 'gemini-3.6-flash');
        Http::fake(['*' => Http::response($this->respuestaEvaluacion())]);

        $this->evaluar();
        GeminiService::obtenerInstancia()->generarRespuestaChat('Hola');

        $urls = Http::recorded()->map(fn ($par) => $par[0]->url())->values();
        $this->assertStringContainsString('gemini-3.6-flash:', $urls[0]);
        $this->assertStringContainsString('gemini-3.5-flash-lite:', $urls[1]);
    }

    /**
     * Si el modelo de evaluación no tiene cuota (429), evalúa el principal y no se pierde la evaluación.
     */
    public function test_si_el_modelo_de_evaluacion_falla_evalua_el_principal(): void
    {
        $this->modelos('gemini-3.5-flash-lite', 'gemini-3.6-flash', 'gemini-3.6-flash');
        Http::fake([
            '*gemini-3.6-flash:*'      => Http::response(['error' => ['message' => 'Sin cuota']], 429),
            '*gemini-3.5-flash-lite:*' => Http::response($this->respuestaEvaluacion()),
        ]);

        $evaluacion = $this->evaluar();

        $this->assertSame('Buen trabajo.', $evaluacion['resumen']);
        Http::assertSentCount(2);
    }

    /**
     * Configura los modelos y crea una instancia nueva del Singleton para que los lea.
     */
    private function modelos(string $principal, string $respaldo, string $evaluacion): void
    {
        config([
            'services.gemini.model'             => $principal,
            'services.gemini.fallback_model'    => $respaldo,
            'services.gemini.modelo_evaluacion' => $evaluacion,
        ]);
        (new \ReflectionProperty(GeminiService::class, 'instancia'))->setValue(null, null);
    }

    /**
     * Evalúa un código pequeño con un criterio, un requisito y una buena práctica.
     */
    private function evaluar(): array
    {
        return GeminiService::obtenerInstancia()->evaluarCodigo(
            ['Funciona'], ['Suma dos números'], ['Nombres claros'], 'function suma(a, b) { return a + b }'
        );
    }

    /**
     * Respuesta válida de Gemini para la evaluación de `evaluar()`.
     */
    private function respuestaEvaluacion(): array
    {
        $json = json_encode([
            'resumen'          => 'Buen trabajo.',
            'criterios'        => [['criterio' => 'Funciona', 'cumplimiento' => 90, 'comentario' => 'Suma bien.']],
            'requisitos'       => [['requisito' => 'Suma dos números', 'cumple' => true, 'comentario' => 'Sí.']],
            'buenas_practicas' => [['practica' => 'Nombres claros', 'cumple' => true, 'comentario' => 'Sí.']],
            'sugerencias'      => ['Agrega pruebas.'],
        ]);

        return ['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => $json]]]]]];
    }

    /**
     * Si Gemini corta la respuesta (por ejemplo, MAX_TOKENS), se anota en el log el motivo.
     */
    public function test_anota_en_el_log_si_gemini_corta_la_respuesta(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response(['candidates' => [[
            'finishReason' => 'MAX_TOKENS',
            'content'      => ['parts' => [['text' => 'Respuesta a medias']]],
        ]]])]);

        GeminiService::obtenerInstancia()->generarRespuestaChat('Hola');

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($mensaje, $datos) => $datos['motivo'] === 'MAX_TOKENS')
            ->once();
    }
}
