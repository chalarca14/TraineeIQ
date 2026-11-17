<?php

namespace Tests\Feature;

use App\Models\ConversacionIa;
use App\Models\EntregaMiniProyecto;
use App\Models\Grupo;
use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Models\GuiaTema;
use App\Models\MensajeIa;
use App\Models\MiniProyecto;
use App\Models\Notificacion;
use App\Models\Recomendacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Borrar una guía (DELETE /api/guias/{id}) o un grupo (DELETE /api/grupos/{id}) no deja datos
 * sueltos: ni contenido, ni entregas, ni chats guiados, ni avisos, ni el archivo.
 */
class EliminarGuiaTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $estudiante;

    private Grupo $grupo;

    /**
     * Un grupo con un instructor y un estudiante inscrito. Los archivos van a un disco falso.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->instructor = User::factory()->create(['rol' => 'instructor']);
        $this->estudiante = User::factory()->create(['rol' => 'estudiante']);
        $this->grupo = Grupo::create([
            'nombre'        => 'ADSO',
            'instructor_id' => $this->instructor->id,
            'codigo_acceso' => 'ABC123',
        ]);
        $this->grupo->estudiantes()->attach($this->estudiante->id);
    }

    /**
     * El instructor borra una guía de su grupo y desaparece todo lo suyo, incluidos los chats
     * guiados y los avisos que no caen en cascada, y el archivo del disco.
     */
    public function test_el_instructor_borra_una_guia_con_todo_lo_suyo(): void
    {
        $guia = $this->guiaCompleta(['origen' => 'grupo', 'grupo_id' => $this->grupo->id]);
        Sanctum::actingAs($this->instructor);

        $this->deleteJson("/api/guias/{$guia->id}")->assertOk();

        $this->assertSame(0, Guia::count());
        $this->assertSame(0, GuiaSemana::count());
        $this->assertSame(0, GuiaTema::count());
        $this->assertSame(0, Recomendacion::count());
        $this->assertSame(0, MiniProyecto::count());
        $this->assertSame(0, EntregaMiniProyecto::count());
        $this->assertSame(0, ConversacionIa::count());
        $this->assertSame(0, MensajeIa::count());
        $this->assertSame(0, Notificacion::count());
        Storage::disk('public')->assertMissing($guia->archivo_pdf);

        // El grupo y sus estudiantes siguen
        $this->assertTrue($this->grupo->fresh()->estudiantes->contains($this->estudiante));
    }

    /**
     * Borrar una guía no toca las otras: ni las del mismo grupo, ni sus chats, avisos o archivo.
     */
    public function test_borrar_una_guia_no_toca_las_demas(): void
    {
        $borrar = $this->guiaCompleta(['origen' => 'grupo', 'grupo_id' => $this->grupo->id]);
        $queda = $this->guiaCompleta(['origen' => 'grupo', 'grupo_id' => $this->grupo->id]);
        // Un chat libre (sin guía) tampoco se toca
        ConversacionIa::create(['estudiante_id' => $this->estudiante->id, 'tipo' => 'libre', 'titulo' => 'Libre']);
        Sanctum::actingAs($this->instructor);

        $this->deleteJson("/api/guias/{$borrar->id}")->assertOk();

        $this->assertSame([$queda->id], Guia::pluck('id')->all());
        $this->assertSame(1, MiniProyecto::count());
        $this->assertSame(1, EntregaMiniProyecto::count());
        // Quedan el chat guiado de la otra guía y el chat libre, y el aviso de la otra guía
        $this->assertSame(2, ConversacionIa::count());
        $this->assertSame(1, Notificacion::count());
        Storage::disk('public')->assertExists($queda->archivo_pdf);
    }

    /**
     * El estudiante borra sus guías del Modo Personal.
     */
    public function test_el_estudiante_borra_su_guia_personal(): void
    {
        $guia = $this->guiaCompleta(['origen' => 'personal', 'usuario_id' => $this->estudiante->id]);
        Sanctum::actingAs($this->estudiante);

        $this->deleteJson("/api/guias/{$guia->id}")->assertOk();

        $this->assertSame(0, Guia::count());
        $this->assertSame(0, EntregaMiniProyecto::count());
    }

    /**
     * Un estudiante no puede borrar una guía de su grupo, aunque la pueda ver.
     */
    public function test_el_estudiante_no_borra_guias_del_grupo(): void
    {
        $guia = $this->guiaCompleta(['origen' => 'grupo', 'grupo_id' => $this->grupo->id]);
        Sanctum::actingAs($this->estudiante);

        $this->deleteJson("/api/guias/{$guia->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo el instructor del grupo puede borrar sus guías.');

        $this->assertSame(1, Guia::count());
    }

    /**
     * Nadie borra lo que no es suyo: ni otro instructor una guía de grupo ajena, ni otro
     * estudiante una guía personal ajena.
     */
    public function test_nadie_borra_guias_ajenas(): void
    {
        $deGrupo = $this->guiaCompleta(['origen' => 'grupo', 'grupo_id' => $this->grupo->id]);
        $personal = $this->guiaCompleta(['origen' => 'personal', 'usuario_id' => $this->estudiante->id]);

        Sanctum::actingAs(User::factory()->create(['rol' => 'instructor']));
        $this->deleteJson("/api/guias/{$deGrupo->id}")->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));
        $this->deleteJson("/api/guias/{$personal->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo puedes borrar tus propias guías.');

        // El instructor del grupo tampoco ve ni borra las guías personales de sus estudiantes
        Sanctum::actingAs($this->instructor);
        $this->deleteJson("/api/guias/{$personal->id}")->assertForbidden();

        $this->assertSame(2, Guia::count());
    }

    /**
     * Una guía que no existe responde 404.
     */
    public function test_guia_inexistente_responde_404(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->deleteJson('/api/guias/999')->assertNotFound();
    }

    /**
     * Borrar el grupo borra sus guías con todo lo suyo, también los chats y avisos que antes
     * quedaban sueltos. Las cuentas y lo de otros grupos o del Modo Personal no se tocan.
     */
    public function test_borrar_el_grupo_borra_todo_lo_de_sus_guias(): void
    {
        $guia = $this->guiaCompleta(['origen' => 'grupo', 'grupo_id' => $this->grupo->id]);
        $personal = $this->guiaCompleta(['origen' => 'personal', 'usuario_id' => $this->estudiante->id]);
        Sanctum::actingAs($this->instructor);

        $this->deleteJson("/api/grupos/{$this->grupo->id}")->assertOk();

        $this->assertNull($this->grupo->fresh());
        $this->assertSame([$personal->id], Guia::pluck('id')->all());
        $this->assertSame(1, ConversacionIa::count());
        $this->assertSame(1, Notificacion::count());
        $this->assertSame(1, EntregaMiniProyecto::count());
        Storage::disk('public')->assertMissing($guia->archivo_pdf);
        Storage::disk('public')->assertExists($personal->archivo_pdf);
        $this->assertNotNull($this->estudiante->fresh());
    }

    /**
     * Una guía con todo lo que puede tener: archivo, semana, tema, recomendación, mini proyecto
     * con una entrega evaluada, un chat guiado con mensajes y un aviso que la nombra en `datos`.
     */
    private function guiaCompleta(array $datos): Guia
    {
        $ruta = 'guias/' . uniqid() . '.pdf';
        Storage::disk('public')->put($ruta, '%PDF-1.4 prueba');

        $guia = Guia::create($datos + ['nombre' => 'Guía', 'archivo_pdf' => $ruta]);

        $semana = $guia->semanas()->create([
            'numero_semana'     => 1,
            'titulo'            => 'Semana 1',
            'resumen'           => 'Resumen.',
            'contenido_texto'   => 'Texto',
            'hash_contenido'    => hash('sha256', 'Texto'),
            'estado_generacion' => 'listo',
        ]);
        GuiaTema::create([
            'guia_id' => $guia->id, 'guia_semana_id' => $semana->id,
            'nombre_tema' => 'Variables', 'descripcion' => 'Descripción', 'orden' => 1,
        ]);
        Recomendacion::create([
            'guia_id' => $guia->id, 'guia_semana_id' => $semana->id, 'orden' => 1,
            'nivel' => 'basico', 'contenido' => ['titulo' => 'Repasa', 'descripcion' => 'Repasa variables'],
        ]);
        $proyecto = $semana->miniProyectos()->create([
            'guia_id'                => $guia->id,
            'orden'                  => 1,
            'titulo'                 => 'Calculadora',
            'descripcion'            => 'Descripción',
            'requisitos_funcionales' => ['Requisito'],
            'buenas_practicas'       => ['Práctica'],
            'criterios_evaluacion'   => [['criterio' => 'Criterio', 'peso' => 100]],
        ]);
        EntregaMiniProyecto::create([
            'mini_proyecto_id' => $proyecto->id,
            'estudiante_id'    => $this->estudiante->id,
            'repo_url'         => 'https://github.com/ana/calculadora',
            'estado'           => 'evaluado',
            'puntaje'          => 80,
        ]);

        $chat = ConversacionIa::create([
            'estudiante_id' => $this->estudiante->id,
            'guia_id'       => $guia->id,
            'tipo'          => 'guiada',
            'titulo'        => 'Dudas',
        ]);
        MensajeIa::create(['conversacion_id' => $chat->id, 'emisor' => 'usuario', 'contenido' => '¿Qué es una variable?']);

        Notificacion::create([
            'usuario_id' => $this->estudiante->id,
            'tipo'       => 'guia_nueva',
            'mensaje'    => 'Tu instructor subió la guía «Guía».',
            'datos'      => ['grupo_id' => $guia->grupo_id, 'guia_id' => $guia->id, 'numero_semana' => 1],
        ]);

        return $guia;
    }
}
