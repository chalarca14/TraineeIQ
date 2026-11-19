<?php

namespace Tests\Feature;

use App\Models\MensajeIa;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * El chat no insiste con un modelo de Gemini que no responde: pasa de inmediato al otro, y si
 * ninguno responde avisa con un 503 sin guardar nada en el historial.
 */
class ChatRapidoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fija los modelos y reinicia el Singleton para que los lea.
     */
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.gemini.model'          => 'gemini-3.5-flash-lite',
            'services.gemini.fallback_model' => 'gemini-3.6-flash',
        ]);
        (new \ReflectionProperty(GeminiService::class, 'instancia'))->setValue(null, null);
        Sleep::fake();
    }

    /**
     * Borra la instancia del Singleton para que otras pruebas no hereden los modelos de aquí.
     */
    protected function tearDown(): void
    {
        (new \ReflectionProperty(GeminiService::class, 'instancia'))->setValue(null, null);
        parent::tearDown();
    }

    /**
     * Con el principal saturado (503), el chat va de una al respaldo: sin reintentos ni esperas.
     */
    public function test_con_503_pasa_de_inmediato_al_otro_modelo(): void
    {
        Http::fake([
            '*gemini-3.5-flash-lite:*' => Http::response(['error' => ['message' => 'Saturado']], 503),
            '*gemini-3.6-flash:*'      => $this->respuesta('Un closure recuerda sus variables.'),
        ]);

        $this->enviar('¿Qué es un closure?')
            ->assertOk()
            ->assertJsonPath('respuesta_ia.contenido', 'Un closure recuerda sus variables.');

        Http::assertSentCount(2);
        Sleep::assertNeverSlept();
    }

    /**
     * Si el principal se queda colgado (tiempo agotado), también pasa al respaldo.
     */
    public function test_si_un_modelo_no_responde_a_tiempo_usa_el_otro(): void
    {
        Http::fake(function ($peticion) {
            if (str_contains($peticion->url(), 'gemini-3.5-flash-lite:')) {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }

            return $this->respuesta('Respuesta del respaldo.');
        });

        $this->enviar('Hola')->assertOk()->assertJsonPath('respuesta_ia.contenido', 'Respuesta del respaldo.');
    }

    /**
     * Si ninguno responde, da una sola vuelta más tras 2 s y luego responde 503 sin guardar
     * la pregunta ni un falso mensaje de la IA.
     */
    public function test_si_ningun_modelo_responde_avisa_con_503_y_no_guarda_nada(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Saturado']], 503)]);

        $this->enviar('¿Qué es un closure?')
            ->assertStatus(503)
            ->assertJsonPath('message', 'La IA está muy ocupada en este momento. Intenta de nuevo en unos segundos.');

        Http::assertSentCount(4); // Dos vueltas por los dos modelos
        Sleep::assertSequence([Sleep::usleep(2_000_000)]);
        $this->assertSame(0, MensajeIa::count());
    }

    /**
     * La pregunta actual va una sola vez en el prompt: ya no se guarda antes de pedir la
     * respuesta, así que no aparece también en el historial.
     */
    public function test_la_pregunta_va_una_sola_vez_en_el_prompt(): void
    {
        Http::fake(['*' => $this->respuesta('Respuesta.')]);

        $this->enviar('pregunta-unica-123')->assertOk();

        $prompt = Http::recorded()->first()[0]['contents'][0]['parts'][0]['text'];
        $this->assertSame(1, substr_count($prompt, 'pregunta-unica-123'));
        $this->assertSame(2, MensajeIa::count()); // La pregunta y la respuesta
    }

    /**
     * Crea una conversación libre con un estudiante y le envía este mensaje.
     */
    private function enviar(string $contenido)
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));
        $conversacion = $this->postJson('/api/conversaciones', ['tipo' => 'libre'])->assertCreated()->json('id');

        return $this->postJson("/api/conversaciones/{$conversacion}/mensajes", ['contenido' => $contenido]);
    }

    /**
     * Respuesta exitosa de Gemini con este texto.
     */
    private function respuesta(string $texto)
    {
        return Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => $texto]]]]]]);
    }
}
