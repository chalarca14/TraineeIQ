<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Models\MiniProyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LecturaSemanasTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private User $estudiante;
    private Grupo $grupo;
    private Guia $guiaGrupo;

    /**
     * Crea un grupo que va en la semana 2 de una guía de 3 semanas.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['rol' => 'instructor']);
        $this->estudiante = User::factory()->create(['rol' => 'estudiante']);

        $this->grupo = Grupo::create([
            'nombre'        => 'ADSO',
            'instructor_id' => $this->instructor->id,
            'codigo_acceso' => 'ABC123',
            'semana_actual' => 2,
        ]);
        $this->grupo->estudiantes()->attach($this->estudiante->id);

        $this->guiaGrupo = Guia::create(['nombre' => 'Guía', 'origen' => 'grupo', 'grupo_id' => $this->grupo->id]);

        foreach ([1, 2, 3] as $numero) {
            $this->crearSemana($this->guiaGrupo, $numero);
        }
    }

    /**
     * El estudiante ve todas las semanas en la lista, con las futuras bloqueadas.
     */
    public function test_la_lista_marca_las_semanas_futuras_como_no_disponibles(): void
    {
        Sanctum::actingAs($this->estudiante);

        $respuesta = $this->getJson("/api/guias/{$this->guiaGrupo->id}/semanas")
            ->assertOk()
            ->assertJsonCount(3, 'semanas')
            ->assertJsonPath('semanas.0.disponible', true)
            ->assertJsonPath('semanas.1.disponible', true)
            ->assertJsonPath('semanas.2.disponible', false)
            ->assertJsonPath('semanas.2.titulo', 'Semana 3');

        $this->assertArrayNotHasKey('contenido_texto', $respuesta->json('semanas.0'));
    }

    /**
     * El estudiante lee la semana actual y las pasadas, pero no las futuras.
     */
    public function test_el_estudiante_solo_lee_hasta_la_semana_actual(): void
    {
        Sanctum::actingAs($this->estudiante);

        $this->leerSemana($this->guiaGrupo, 1)->assertOk();

        $this->leerSemana($this->guiaGrupo, 2)
            ->assertOk()
            ->assertJsonCount(1, 'semana.recomendaciones')
            ->assertJsonCount(1, 'semana.mini_proyectos')
            ->assertJsonPath('semana.disponible', true);

        $this->leerSemana($this->guiaGrupo, 3)
            ->assertForbidden()
            ->assertJsonPath('message', 'Esta semana aún no está disponible.');
    }

    /**
     * Un mini proyecto de una semana futura tampoco se puede leer por su id.
     */
    public function test_el_mini_proyecto_respeta_la_semana_liberada(): void
    {
        Sanctum::actingAs($this->estudiante);

        $this->getJson('/api/mini-proyectos/' . $this->miniProyecto(2)->id)
            ->assertOk()
            ->assertJsonPath('mini_proyecto.requisitos_funcionales.0', 'Requisito')
            ->assertJsonPath('mini_proyecto.semana.numero_semana', 2)
            ->assertJsonMissingPath('mini_proyecto.guia');

        $this->getJson('/api/mini-proyectos/' . $this->miniProyecto(3)->id)->assertForbidden();
    }

    /**
     * Pedir generar una semana futura no revela su contenido al estudiante.
     */
    public function test_generar_una_semana_futura_no_revela_el_contenido(): void
    {
        Sanctum::actingAs($this->estudiante);

        $respuesta = $this->postJson("/api/guias/{$this->guiaGrupo->id}/semanas/3/generar")
            ->assertOk()
            ->assertJsonPath('semana.disponible', false);

        $this->assertArrayNotHasKey('recomendaciones', $respuesta->json('semana'));
        $this->assertArrayNotHasKey('mini_proyectos', $respuesta->json('semana'));
    }

    /**
     * El instructor dueño lee todas las semanas, también las que no ha liberado.
     */
    public function test_el_instructor_dueno_lee_todas_las_semanas(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->leerSemana($this->guiaGrupo, 3)->assertOk()->assertJsonPath('semana.disponible', true);
        $this->getJson('/api/mini-proyectos/' . $this->miniProyecto(3)->id)->assertOk();
    }

    /**
     * Quien no pertenece al grupo recibe 403 en cada lectura.
     */
    public function test_ajenos_al_grupo_reciben_403(): void
    {
        foreach (['estudiante', 'instructor'] as $rol) {
            Sanctum::actingAs(User::factory()->create(['rol' => $rol]));

            $this->getJson("/api/guias/{$this->guiaGrupo->id}")->assertForbidden();
            $this->getJson("/api/guias/{$this->guiaGrupo->id}/semanas")->assertForbidden();
            $this->leerSemana($this->guiaGrupo, 1)->assertForbidden();
            $this->getJson('/api/mini-proyectos/' . $this->miniProyecto(1)->id)
                ->assertForbidden()
                ->assertJsonPath('message', 'No tienes acceso a esta guía.');
        }
    }

    /**
     * En Modo Personal solo la semana 1 empieza abierta: las demás se abren con experiencia.
     */
    public function test_en_modo_personal_solo_la_primera_semana_empieza_liberada(): void
    {
        $guia = $this->guiaPersonal($this->estudiante);
        Sanctum::actingAs($this->estudiante);

        $this->getJson("/api/guias/{$guia->id}/semanas")
            ->assertOk()
            ->assertJsonPath('semanas.0.disponible', true)
            ->assertJsonPath('semanas.2.disponible', false);

        $this->leerSemana($guia, 1)->assertOk();
        $this->leerSemana($guia, 3)->assertForbidden();
    }

    /**
     * Una guía personal es privada: ni otro estudiante ni el instructor la ven.
     */
    public function test_la_guia_personal_es_privada(): void
    {
        $guia = $this->guiaPersonal($this->estudiante);
        $proyecto = $guia->semanas()->first()->miniProyectos()->first();

        foreach ([User::factory()->create(['rol' => 'estudiante']), $this->instructor] as $usuario) {
            Sanctum::actingAs($usuario);

            $this->getJson("/api/guias/{$guia->id}")->assertForbidden();
            $this->getJson("/api/guias/{$guia->id}/semanas")->assertForbidden();
            $this->leerSemana($guia, 1)->assertForbidden();
            $this->getJson("/api/mini-proyectos/{$proyecto->id}")->assertForbidden();
        }
    }

    /**
     * Cuando el instructor libera una semana, el estudiante ya puede leerla.
     */
    public function test_el_instructor_libera_una_semana(): void
    {
        Sanctum::actingAs($this->instructor);
        $this->liberar(3)->assertOk()->assertJsonPath('semana_actual', 3);

        Sanctum::actingAs($this->estudiante);
        $this->leerSemana($this->guiaGrupo, 3)->assertOk();
    }

    /**
     * Si el instructor baja la semana, las siguientes vuelven a bloquearse.
     */
    public function test_bajar_la_semana_vuelve_a_bloquear(): void
    {
        Sanctum::actingAs($this->instructor);
        $this->liberar(1)->assertOk();

        Sanctum::actingAs($this->estudiante);
        $this->leerSemana($this->guiaGrupo, 2)->assertForbidden();
    }

    /**
     * Solo el instructor dueño libera semanas, y con un número válido.
     */
    public function test_liberar_exige_al_instructor_dueno_y_un_numero_valido(): void
    {
        Sanctum::actingAs($this->estudiante);
        $this->liberar(3)->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['rol' => 'instructor']));
        $this->liberar(3)->assertForbidden();

        Sanctum::actingAs($this->instructor);
        $this->liberar(0)->assertUnprocessable();
        $this->liberar(53)->assertUnprocessable();

        $this->assertSame(2, $this->grupo->fresh()->semana_actual);
    }

    /**
     * Las rutas de generación a pedido y las viejas de mini proyectos ya no existen.
     */
    public function test_las_rutas_viejas_ya_no_existen(): void
    {
        Http::fake();
        Sanctum::actingAs($this->estudiante);

        $this->postJson('/api/recomendaciones', ['tema_id' => 1])->assertNotFound();
        $this->getJson('/api/recomendaciones/historial')->assertNotFound();
        $this->getJson('/api/mini-proyectos')->assertNotFound();
        $this->getJson("/api/mini-proyectos/guia/{$this->guiaGrupo->id}")->assertNotFound();

        $this->postJson('/api/mini-proyectos/generar', ['guia_id' => 1])->assertNotFound();
        $this->postJson('/api/mini-proyectos', [])->assertNotFound();

        Http::assertNothingSent();
    }

    /**
     * El estudiante de grupo no recibe los temas de semanas que no se han liberado.
     */
    public function test_el_estudiante_no_recibe_temas_de_semanas_bloqueadas(): void
    {
        Sanctum::actingAs($this->estudiante);

        $detalle = $this->getJson("/api/guias/{$this->guiaGrupo->id}")
            ->assertOk()
            ->assertJsonMissingPath('guia.grupo')
            ->assertJsonMissingPath('guia.temas.0.semana');
        $this->assertSame(['Tema 1', 'Tema 2'], array_column($detalle->json('guia.temas'), 'nombre_tema'));

        $lista = $this->getJson('/api/guias')->assertOk();
        $this->assertSame(['Tema 1', 'Tema 2'], array_column($lista->json('data.0.temas'), 'nombre_tema'));
    }

    /**
     * Al liberar una semana, sus temas aparecen para el estudiante.
     */
    public function test_liberar_una_semana_muestra_sus_temas(): void
    {
        $this->grupo->update(['semana_actual' => 3]);
        Sanctum::actingAs($this->estudiante);

        $this->getJson("/api/guias/{$this->guiaGrupo->id}")->assertOk()->assertJsonCount(3, 'guia.temas');
    }

    /**
     * El instructor dueño recibe los temas de todas las semanas; en Modo Personal
     * solo llegan los de las semanas ya abiertas con experiencia.
     */
    public function test_instructor_recibe_todos_los_temas_y_modo_personal_los_abiertos(): void
    {
        Sanctum::actingAs($this->instructor);
        $this->getJson("/api/guias/{$this->guiaGrupo->id}")->assertOk()->assertJsonCount(3, 'guia.temas');
        $this->getJson('/api/guias')->assertOk()->assertJsonCount(3, 'data.0.temas');

        $guia = $this->guiaPersonal($this->estudiante);
        Sanctum::actingAs($this->estudiante);
        $this->getJson("/api/guias/{$guia->id}")->assertOk()->assertJsonCount(1, 'guia.temas');
    }

    /**
     * Los datos del grupo incluyen correos: solo los ven su instructor y sus estudiantes.
     */
    public function test_solo_los_miembros_ven_el_grupo(): void
    {
        foreach ([$this->instructor, $this->estudiante] as $miembro) {
            Sanctum::actingAs($miembro);
            $this->getJson("/api/grupos/{$this->grupo->id}")->assertOk();
        }

        foreach (['estudiante', 'instructor'] as $rol) {
            Sanctum::actingAs(User::factory()->create(['rol' => $rol]));

            $this->getJson("/api/grupos/{$this->grupo->id}")
                ->assertForbidden()
                ->assertJsonPath('message', 'No tienes acceso a este grupo.')
                ->assertJsonMissingPath('data');
        }
    }

    /**
     * Pide liberar semanas en el grupo de prueba.
     */
    private function liberar(int $semana)
    {
        return $this->patchJson("/api/grupos/{$this->grupo->id}/semana-actual", ['semana_actual' => $semana]);
    }

    /**
     * Pide el contenido de una semana.
     */
    private function leerSemana(Guia $guia, int $numero)
    {
        return $this->getJson("/api/guias/{$guia->id}/semanas/{$numero}");
    }

    /**
     * Devuelve el mini proyecto de una semana de la guía de grupo.
     */
    private function miniProyecto(int $numero): MiniProyecto
    {
        return $this->guiaGrupo->semanas()
            ->where('numero_semana', $numero)
            ->firstOrFail()
            ->miniProyectos()
            ->firstOrFail();
    }

    /**
     * Crea una guía personal de 3 semanas.
     */
    private function guiaPersonal(User $dueno): Guia
    {
        $guia = Guia::create(['nombre' => 'Mi guía', 'origen' => 'personal', 'usuario_id' => $dueno->id]);

        foreach ([1, 2, 3] as $numero) {
            $this->crearSemana($guia, $numero);
        }

        return $guia;
    }

    /**
     * Crea una semana lista, con una recomendación y un mini proyecto.
     */
    private function crearSemana(Guia $guia, int $numero): GuiaSemana
    {
        $semana = $guia->semanas()->create([
            'numero_semana'     => $numero,
            'titulo'            => "Semana {$numero}",
            'resumen'           => 'Resumen.',
            'contenido_texto'   => "Texto {$numero}",
            'hash_contenido'    => hash('sha256', "Texto {$numero}"),
            'estado_generacion' => 'listo',
        ]);

        $semana->temas()->create([
            'guia_id'     => $guia->id,
            'nombre_tema' => "Tema {$numero}",
            'descripcion' => 'Descripción del tema.',
            'orden'       => $numero,
        ]);

        $semana->recomendaciones()->create([
            'guia_id'   => $guia->id,
            'orden'     => 1,
            'nivel'     => 'basico',
            'contenido' => ['titulo' => 'Título', 'descripcion' => 'Descripción'],
        ]);

        $semana->miniProyectos()->create([
            'guia_id'                => $guia->id,
            'orden'                  => 1,
            'titulo'                 => 'Mini proyecto',
            'descripcion'            => 'Descripción',
            'requisitos_funcionales' => ['Requisito'],
            'buenas_practicas'       => ['Práctica'],
            'criterios_evaluacion'   => [['criterio' => 'Criterio', 'peso' => 100]],
        ]);

        return $semana;
    }
}
