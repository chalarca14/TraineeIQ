<?php

namespace Tests\Feature;

use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Models\GuiaTema;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Chat guiado: responde lo que trata la guia (incluida la pregunta "¿de que trata la guia?")
 * y sigue mandando al chat libre lo que no tiene que ver con ella (RNF-17).
 */
class ChatGuiadoTest extends TestCase
{
    use RefreshDatabase;

    private const REDIRECCION = 'Ese tema se sale de esta guia. Lo puedes explorar en Conversar con la IA (modo libre).';

    private User $estudiante;
    private Guia $guia;

    /**
     * Crea un estudiante con una guia personal "Desarrollo web con PHP", una semana y dos temas.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->reiniciarSingleton();

        $this->estudiante = User::factory()->create(['rol' => 'estudiante']);
        $this->guia = Guia::create([
            'nombre' => 'Desarrollo web con PHP', 'archivo_pdf' => 'guias/x.pdf',
            'usuario_id' => $this->estudiante->id, 'origen' => 'personal',
        ]);
        $semana = GuiaSemana::create([
            'guia_id' => $this->guia->id, 'numero_semana' => 1, 'titulo' => 'Formularios y sesiones',
            'resumen' => 'Validación de formularios y manejo de sesiones.', 'contenido_texto' => 'Texto.',
            'hash_contenido' => 'h1', 'estado_generacion' => 'listo',
        ]);
        GuiaTema::create(['guia_id' => $this->guia->id, 'guia_semana_id' => $semana->id, 'nombre_tema' => 'PHP y Git', 'descripcion' => 'Primer proyecto versionado.', 'orden' => 1]);
        GuiaTema::create(['guia_id' => $this->guia->id, 'guia_semana_id' => $semana->id, 'nombre_tema' => 'Programación orientada a objetos', 'descripcion' => 'Clases y herencia.', 'orden' => 2]);
    }

    /**
     * Borra la instancia del Singleton para no heredar la configuracion de otras pruebas.
     */
    protected function tearDown(): void
    {
        $this->reiniciarSingleton();
        parent::tearDown();
    }

    /**
     * El caso visto en produccion: "¿de que trata la guia?" se responde, y el prompt ya
     * lleva el nombre de la guia, sus semanas y la regla de que esa pregunta es valida.
     */
    public function test_responde_de_que_trata_la_guia(): void
    {
        $respuesta = 'Esta guía trata del desarrollo web: en la semana 1 verás formularios y sesiones.';
        $this->fingirGemini($respuesta);

        $this->assertSame($respuesta, $this->preguntar('¿De qué trata la guía?'));

        Http::assertSent(function (Request $request) {
            $prompt = $request->data()['contents'][0]['parts'][0]['text'] ?? '';

            return str_contains($prompt, 'Nombre de la guia: Desarrollo web con PHP')
                && str_contains($prompt, 'Semana 1: Formularios y sesiones')
                && str_contains($prompt, 'Las preguntas sobre la guia misma SIEMPRE tienen relacion');
        });
    }

    /**
     * Las siglas cuentan en el filtro del backend: antes "Git" no contaba (3 letras) y una
     * respuesta correcta sobre el tema "PHP y Git" se cambiaba por el mensaje de redireccion.
     */
    public function test_el_filtro_acepta_siglas(): void
    {
        $this->fingirGemini('Usa Git para versionar cada cambio.');
        $this->assertSame('Usa Git para versionar cada cambio.', $this->preguntar('¿Cómo guardo mis cambios?'));
    }

    /**
     * El filtro compara tambien con las semanas: antes solo miraba los nombres de los temas,
     * y una respuesta sobre "formularios" y "sesión" (titulo de la semana 1) se rechazaba.
     */
    public function test_el_filtro_compara_con_las_semanas(): void
    {
        $this->fingirGemini('Valida los formularios en el servidor y guarda los datos en la sesión.');
        $this->assertSame('Valida los formularios en el servidor y guarda los datos en la sesión.', $this->preguntar('¿Qué hago esta semana?'));
    }

    /**
     * Si la IA marca la pregunta como ajena, se sigue mandando al chat libre.
     */
    public function test_pregunta_ajena_con_marcador_se_redirige(): void
    {
        $this->fingirGemini('FUERA_DE_ALCANCE: la pregunta es de cocina.');
        $this->assertSame(self::REDIRECCION, $this->preguntar('¿Cómo hago arepas?'));
    }

    /**
     * Si la IA responde algo que no comparte ninguna palabra con la guia, el backend la
     * redirige aunque no haya usado el marcador.
     */
    public function test_respuesta_ajena_sin_marcador_se_redirige(): void
    {
        $this->fingirGemini('Mezcla harina de maíz, agua y sal; amasa y asa en el budare.');
        $this->assertSame(self::REDIRECCION, $this->preguntar('¿Cómo hago arepas?'));
    }

    /**
     * Hace que Gemini responda siempre el texto dado.
     */
    private function fingirGemini(string $texto): void
    {
        Http::fake(['*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => $texto]]]]]])]);
    }

    /**
     * Envia una pregunta al chat guiado de la guia y devuelve el texto de la respuesta.
     */
    private function preguntar(string $pregunta): string
    {
        Sanctum::actingAs($this->estudiante);
        $conversacion = $this->postJson('/api/conversaciones', ['tipo' => 'guiada', 'guia_id' => $this->guia->id])
            ->assertCreated()
            ->json('id');

        return $this->postJson("/api/conversaciones/{$conversacion}/mensajes", ['contenido' => $pregunta])
            ->assertOk()
            ->json('respuesta_ia.contenido');
    }

    /**
     * Borra la instancia guardada del Singleton de Gemini.
     */
    private function reiniciarSingleton(): void
    {
        $propiedad = new \ReflectionProperty(GeminiService::class, 'instancia');
        $propiedad->setAccessible(true);
        $propiedad->setValue(null, null);
    }
}
