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
