<?php

namespace Tests\Feature;

use App\Models\ConversacionIa;
use App\Models\MensajeIa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Borrar conversaciones del chat con IA: una sola (DELETE /conversaciones/{id}) o todo el
 * historial (DELETE /conversaciones, con ?tipo=libre o ?tipo=guiada opcional). Cada estudiante
 * solo puede borrar lo suyo, y los mensajes se van con su conversacion (cascade).
 */
class BorrarConversacionesTest extends TestCase
{
    use RefreshDatabase;

    private User $estudiante;
    private User $otroEstudiante;

    /**
     * Crea dos estudiantes: el que hace las peticiones y otro cuyas conversaciones no deben tocarse.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->estudiante = User::factory()->create(['rol' => 'estudiante']);
        $this->otroEstudiante = User::factory()->create(['rol' => 'estudiante']);
    }

    /**
     * Crea una conversacion del tipo pedido para el usuario dado, con un mensaje de cada emisor.
     * La guiada va sin guia_id: para borrar no importa la guia (la columna acepta null).
     */
    private function crearConversacion(User $usuario, string $tipo = 'libre'): ConversacionIa
    {
        $conversacion = ConversacionIa::create([
            'estudiante_id' => $usuario->id,
            'tipo'          => $tipo,
            'titulo'        => $tipo === 'libre' ? 'Conversacion libre' : 'Conversacion sobre una guia',
        ]);

        MensajeIa::create(['conversacion_id' => $conversacion->id, 'emisor' => 'usuario', 'contenido' => 'Hola']);
        MensajeIa::create(['conversacion_id' => $conversacion->id, 'emisor' => 'ia', 'contenido' => 'Hola, ¿en que te ayudo?']);

        return $conversacion;
    }

    /**
     * Borrar una conversacion propia la quita junto con sus mensajes y deja las demas.
     */
    public function test_borra_una_conversacion_propia_con_sus_mensajes(): void
    {
        $borrar = $this->crearConversacion($this->estudiante);
        $queda = $this->crearConversacion($this->estudiante);
        Sanctum::actingAs($this->estudiante);

        $this->deleteJson("/api/conversaciones/{$borrar->id}")->assertOk();

        $this->assertDatabaseMissing('conversaciones_ia', ['id' => $borrar->id]);
        $this->assertDatabaseMissing('mensajes_ia', ['conversacion_id' => $borrar->id]);
        $this->assertDatabaseHas('conversaciones_ia', ['id' => $queda->id]);
        $this->assertSame(2, MensajeIa::where('conversacion_id', $queda->id)->count());
    }

    /**
     * Borrar una conversacion de otro estudiante responde 403 y no borra nada.
     */
    public function test_no_borra_una_conversacion_ajena(): void
    {
        $ajena = $this->crearConversacion($this->otroEstudiante);
        Sanctum::actingAs($this->estudiante);

        $this->deleteJson("/api/conversaciones/{$ajena->id}")->assertForbidden();

        $this->assertDatabaseHas('conversaciones_ia', ['id' => $ajena->id]);
        $this->assertSame(2, MensajeIa::where('conversacion_id', $ajena->id)->count());
    }

    /**
     * Sin tipo, borra todas las conversaciones del estudiante (libres y guiadas) con sus mensajes.
     */
    public function test_borra_todo_el_historial_sin_tipo(): void
    {
        $this->crearConversacion($this->estudiante, 'libre');
        $this->crearConversacion($this->estudiante, 'libre');
        $this->crearConversacion($this->estudiante, 'guiada');
        Sanctum::actingAs($this->estudiante);

        $this->deleteJson('/api/conversaciones')
            ->assertOk()
            ->assertJson(['eliminadas' => 3]);

        $this->assertSame(0, ConversacionIa::where('estudiante_id', $this->estudiante->id)->count());
        $this->assertSame(0, MensajeIa::count());
    }

    /**
     * Con tipo=libre solo borra las libres; las guiadas se quedan. Y al reves con tipo=guiada.
     */
    public function test_borra_solo_el_tipo_pedido(): void
    {
        $this->crearConversacion($this->estudiante, 'libre');
        $this->crearConversacion($this->estudiante, 'libre');
        $guiada = $this->crearConversacion($this->estudiante, 'guiada');
        Sanctum::actingAs($this->estudiante);

        $this->deleteJson('/api/conversaciones?tipo=libre')
            ->assertOk()
            ->assertJson(['eliminadas' => 2]);

        $this->assertSame(0, ConversacionIa::where('tipo', 'libre')->count());
        $this->assertDatabaseHas('conversaciones_ia', ['id' => $guiada->id]);

        $this->deleteJson('/api/conversaciones?tipo=guiada')
            ->assertOk()
            ->assertJson(['eliminadas' => 1]);

        $this->assertDatabaseMissing('conversaciones_ia', ['id' => $guiada->id]);
    }

    /**
     * Borrar el historial nunca toca las conversaciones de otro estudiante.
     */
    public function test_borrar_historial_no_toca_las_de_otro_estudiante(): void
    {
        $this->crearConversacion($this->estudiante, 'libre');
        $ajenaLibre = $this->crearConversacion($this->otroEstudiante, 'libre');
        $ajenaGuiada = $this->crearConversacion($this->otroEstudiante, 'guiada');
        Sanctum::actingAs($this->estudiante);

        $this->deleteJson('/api/conversaciones')
            ->assertOk()
            ->assertJson(['eliminadas' => 1]);

        $this->assertDatabaseHas('conversaciones_ia', ['id' => $ajenaLibre->id]);
        $this->assertDatabaseHas('conversaciones_ia', ['id' => $ajenaGuiada->id]);
        $this->assertSame(4, MensajeIa::count());
    }

    /**
     * Un tipo que no es libre ni guiada responde 422 y no borra nada.
     */
    public function test_tipo_invalido_responde_422(): void
    {
        $this->crearConversacion($this->estudiante, 'libre');
        Sanctum::actingAs($this->estudiante);

        $this->deleteJson('/api/conversaciones?tipo=otro')
            ->assertStatus(422)
            ->assertJsonValidationErrors('tipo');

        $this->assertSame(1, ConversacionIa::count());
    }

    /**
     * Sin conversaciones, borrar el historial responde 200 con eliminadas = 0.
     */
    public function test_historial_vacio_responde_cero(): void
    {
        Sanctum::actingAs($this->estudiante);

        $this->deleteJson('/api/conversaciones')
            ->assertOk()
            ->assertJson(['eliminadas' => 0]);
    }

    /**
     * La lista acepta ?tipo= y su "total" cuenta solo las de ese tipo (el panel lo usa para
     * decir cuantas se borraran).
     */
    public function test_la_lista_filtra_por_tipo(): void
    {
        $this->crearConversacion($this->estudiante, 'libre');
        $this->crearConversacion($this->estudiante, 'libre');
        $this->crearConversacion($this->estudiante, 'guiada');
        $this->crearConversacion($this->otroEstudiante, 'libre');
        Sanctum::actingAs($this->estudiante);

        $this->getJson('/api/conversaciones?tipo=libre')->assertOk()->assertJson(['total' => 2]);
        $this->getJson('/api/conversaciones?tipo=guiada')->assertOk()->assertJson(['total' => 1]);
        $this->getJson('/api/conversaciones')->assertOk()->assertJson(['total' => 3]);
        $this->getJson('/api/conversaciones?tipo=otro')->assertStatus(422);
    }

    /**
     * Sin iniciar sesion no se puede borrar el historial.
     */
    public function test_sin_sesion_responde_401(): void
    {
        $this->deleteJson('/api/conversaciones')->assertUnauthorized();
    }
}
