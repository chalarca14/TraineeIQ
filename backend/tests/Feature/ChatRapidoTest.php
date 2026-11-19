<?php

namespace Tests\Feature;

use App\Models\MensajeIa;
use App\Models\User;
use App\Services\GeminiService;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Psr\Http\Message\RequestInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Carrera escalonada del chat: se pide al primer modelo; si falla se lanza el siguiente de
 * inmediato, y si tarda 6 s se lanza también el siguiente sin cancelar el primero. Gana la
 * primera respuesta buena. Si nadie responde en 60 s, 503 sin guardar nada.
 *
 * El reloj es falso: cada pausa de la carrera adelanta la hora de Laravel (syncWithCarbon), así
 * que "esperar 6 s" no tarda de verdad. Un modelo que "no responde" devuelve una promesa que
 * nunca se cumple.
 */
class ChatRapidoTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, float> Segundo (desde el inicio) en que se lanzó cada modelo */
    private array $lanzados = [];

    /**
     * Fija tres modelos para el chat, congela el reloj y reinicia el Singleton para que los lea.
     */
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.gemini.model'                 => 'modelo-a',
            'services.gemini.fallback_model'        => 'modelo-c',
            'services.gemini.modelos_chat'          => 'modelo-a,modelo-b,modelo-c',
            'services.gemini.chat_segundos_escalon' => 6,
        ]);
        $this->reiniciarSingleton();
        Carbon::setTestNow(Carbon::parse('2026-11-19 10:00:00'));
        Sleep::fake(syncWithCarbon: true);
    }

    /**
     * Borra la instancia del Singleton para que otras pruebas no hereden los modelos de aquí.
     */
    protected function tearDown(): void
    {
        $this->reiniciarSingleton();
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Caso 1, Google bien: responde el primero y los demás nunca se lanzan (una sola llamada).
     */
    public function test_si_el_primero_responde_no_se_lanzan_los_demas(): void
    {
        $this->modelos(['modelo-a' => 'ok']);

        $this->enviar('Hola')->assertOk()->assertJsonPath('respuesta_ia.contenido', 'Respuesta de modelo-a');

        $this->assertSame(['modelo-a'], array_keys($this->lanzados));
    }

    /**
     * Caso 2: el primero falla rápido (503) y el siguiente se lanza de inmediato, sin esperar 6 s.
     */
    public function test_si_el_primero_falla_lanza_el_siguiente_de_inmediato(): void
    {
        $this->modelos(['modelo-a' => 503, 'modelo-b' => 'ok']);

        $this->enviar('Hola')->assertOk()->assertJsonPath('respuesta_ia.contenido', 'Respuesta de modelo-b');

        $this->assertLessThan(0.2, $this->lanzados['modelo-b']);
        $this->assertArrayNotHasKey('modelo-c', $this->lanzados);
    }

    /**
     * Caso 3: el primero se queda colgado; a los 6 s se lanza el segundo sin cancelar el
     * primero, y gana el segundo.
     */
    public function test_si_el_primero_tarda_lanza_el_siguiente_a_los_6_segundos(): void
    {
        $this->modelos(['modelo-a' => 'colgado', 'modelo-b' => 'ok']);

        $this->enviar('Hola')->assertOk()->assertJsonPath('respuesta_ia.contenido', 'Respuesta de modelo-b');

        $this->assertEqualsWithDelta(6, $this->lanzados['modelo-b'], 0.2);
        $this->assertArrayNotHasKey('modelo-c', $this->lanzados);
    }

    /**
     * Caso 3 bis: el primero cuelga y el segundo falla rápido; el tercero sale sin esperar
     * otro escalón.
     */
    public function test_un_fallo_rapido_lanza_el_tercero_sin_esperar(): void
    {
        $this->modelos(['modelo-a' => 'colgado', 'modelo-b' => 429, 'modelo-c' => 'ok']);

        $this->enviar('Hola')->assertOk()->assertJsonPath('respuesta_ia.contenido', 'Respuesta de modelo-c');

        $this->assertEqualsWithDelta(6, $this->lanzados['modelo-c'], 0.2);
    }

    /**
     * Caso 5: nadie responde. Los tres salen a los 0, 6 y 12 s, y a los 60 s el chat responde
     * 503 sin guardar la pregunta ni un falso mensaje de la IA.
     */
    public function test_si_nadie_responde_avisa_a_los_60_segundos_y_no_guarda_nada(): void
    {
        $this->modelos(['modelo-a' => 'colgado', 'modelo-b' => 'colgado', 'modelo-c' => 'colgado']);
        $inicio = now();

        $this->enviar('¿Qué es un closure?')
            ->assertStatus(503)
            ->assertJsonPath('message', 'La IA está muy ocupada en este momento. Intenta de nuevo en unos segundos.');

        $this->assertEqualsWithDelta(0, $this->lanzados['modelo-a'], 0.2);
        $this->assertEqualsWithDelta(6, $this->lanzados['modelo-b'], 0.2);
        $this->assertEqualsWithDelta(12, $this->lanzados['modelo-c'], 0.2);
        $this->assertEqualsWithDelta(60, $inicio->diffInSeconds(now()), 0.2);
        $this->assertSame(0, MensajeIa::count());
    }

    /**
     * Si todos fallan rápido, no se espera hasta los 60 s: el 503 sale de una.
     */
    public function test_si_todos_fallan_rapido_avisa_sin_esperar(): void
    {
        $this->modelos(['modelo-a' => 503, 'modelo-b' => 503, 'modelo-c' => 429]);
        $inicio = now();

        $this->enviar('Hola')->assertStatus(503);

        $this->assertLessThan(1, $inicio->diffInSeconds(now()));
    }

    /**
     * Sin GEMINI_MODELOS_CHAT, el chat usa el modelo principal y luego el de respaldo.
     */
    public function test_sin_lista_usa_el_principal_y_el_respaldo(): void
    {
        config(['services.gemini.modelos_chat' => null]);
        $this->reiniciarSingleton();
        $this->modelos(['modelo-a' => 503, 'modelo-c' => 'ok']);

        $this->enviar('Hola')->assertOk()->assertJsonPath('respuesta_ia.contenido', 'Respuesta de modelo-c');

        $this->assertSame(['modelo-a', 'modelo-c'], array_keys($this->lanzados));
    }

    /**
     * La pregunta actual va una sola vez en el prompt: ya no se guarda antes de pedir la
     * respuesta, así que no aparece también en el historial.
     */
    public function test_la_pregunta_va_una_sola_vez_en_el_prompt(): void
    {
        $prompt = null;
        Http::fake(function ($peticion) use (&$prompt) {
            $prompt = $peticion['contents'][0]['parts'][0]['text'];

            return $this->respuesta('Respuesta.');
        });

        $this->enviar('pregunta-unica-123')->assertOk();

        $this->assertSame(1, substr_count($prompt, 'pregunta-unica-123'));
        $this->assertSame(2, MensajeIa::count()); // La pregunta y la respuesta
    }

    /**
     * Simula a Gemini: cada modelo responde 'ok', un código de error o 'colgado' (nunca
     * responde). Anota en qué segundo se lanzó cada modelo.
     *
     * Http::fake siempre resuelve la petición en el acto, así que no sirve para un modelo
     * colgado. Para esos, el fake devuelve null (la petición sigue al manejador) y el manejador
     * de curl se cambia por uno que devuelve una promesa que nunca se cumple.
     */
    private function modelos(array $comportamientos): void
    {
        $inicio = now();

        $this->app->bind(CurlMultiHandler::class, fn () => new class extends CurlMultiHandler
        {
            public function __invoke(RequestInterface $request, array $options): PromiseInterface
            {
                return new Promise(); // Nunca se cumple: el modelo "no responde"
            }

            public function tick(): void
            {
                // No hay conexiones reales que avanzar; solo se entregan las respuestas ya
                // listas (las de Http::fake), como hace el tick() real
                Utils::queue()->run();
            }
        });

        Http::fake(function ($peticion) use ($comportamientos, $inicio) {
            preg_match('#models/([^:]+):#', $peticion->url(), $coincidencia);
            $modelo = $coincidencia[1];
            $this->lanzados[$modelo] = $inicio->diffInMilliseconds(now()) / 1000;

            return match ($comportamientos[$modelo] ?? 503) {
                'ok'      => $this->respuesta("Respuesta de {$modelo}"),
                'colgado' => null, // Pasa al manejador que nunca responde
                default   => Http::response(['error' => ['message' => 'Falla']], $comportamientos[$modelo]),
            };
        });
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

    /**
     * Borra la instancia del Singleton para que lea de nuevo la configuración.
     */
    private function reiniciarSingleton(): void
    {
        (new \ReflectionProperty(GeminiService::class, 'instancia'))->setValue(null, null);
    }
}
