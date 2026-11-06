<?php

namespace Tests\Feature;

use App\Models\EntregaMiniProyecto;
use App\Models\Grupo;
use App\Models\Guia;
use App\Models\MiniProyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EntregaMiniProyectoTest extends TestCase
{
    use RefreshDatabase;

    private const REPO = 'https://github.com/ejemplo/calculadora';
    private const OTRO_REPO = 'https://github.com/ejemplo/gestor.de-gastos_2';

    private User $instructor;
    private User $estudiante;
    private Grupo $grupo;
    private MiniProyecto $proyecto;

    /**
     * Crea un grupo en la semana 1, con un mini proyecto en esa semana.
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
        ]);
        $this->grupo->estudiantes()->attach($this->estudiante->id);

        $guia = Guia::create(['nombre' => 'Guía', 'origen' => 'grupo', 'grupo_id' => $this->grupo->id]);
        $this->proyecto = $this->crearProyecto($guia, 1);
    }

    /**
     * El estudiante guarda su repositorio, y después lo puede cambiar sin duplicar la fila.
     */
    public function test_guarda_y_cambia_el_repositorio(): void
    {
        Sanctum::actingAs($this->estudiante);

        $this->guardar(self::REPO)
            ->assertCreated()
            ->assertJsonPath('entrega.repo_url', self::REPO);

        $this->guardar(self::OTRO_REPO)
            ->assertOk()
            ->assertJsonPath('entrega.repo_url', self::OTRO_REPO);

        $this->assertSame(1, EntregaMiniProyecto::count());
    }

    /**
     * Sin entrega, la consulta responde null y no un error.
     */
    public function test_consultar_sin_entrega_responde_null(): void
    {
        Sanctum::actingAs($this->estudiante);

        $this->consultar()->assertOk()->assertJsonPath('entrega', null);
    }

    /**
     * Cada estudiante recibe solo su propia entrega.
     */
    public function test_cada_estudiante_ve_solo_su_entrega(): void
    {
        $companero = User::factory()->create(['rol' => 'estudiante']);
        $this->grupo->estudiantes()->attach($companero->id);

        Sanctum::actingAs($this->estudiante);
        $this->guardar(self::REPO)->assertCreated();

        Sanctum::actingAs($companero);
        $this->consultar()->assertOk()->assertJsonPath('entrega', null);
        $this->guardar(self::OTRO_REPO)->assertCreated();

        Sanctum::actingAs($this->estudiante);
        $this->consultar()->assertOk()->assertJsonPath('entrega.repo_url', self::REPO);
    }

    /**
     * Solo se acepta la forma exacta de un repositorio de GitHub.
     *
     * @dataProvider linksInvalidos
     */
    public function test_rechaza_links_que_no_son_de_github(string $link): void
    {
        Sanctum::actingAs($this->estudiante);

        $this->guardar($link)->assertUnprocessable();

        $this->assertSame(0, EntregaMiniProyecto::count());
    }

    /**
     * Links que parecen válidos pero no deben pasar.
     */
    public static function linksInvalidos(): array
    {
        return [
            'sin https'          => ['http://github.com/ejemplo/repo'],
            'otro sitio'         => ['https://gitlab.com/ejemplo/repo'],
            'dominio parecido'   => ['https://github.com.malo.com/ejemplo/repo'],
            'con usuario'        => ['https://usuario@github.com/ejemplo/repo'],
            'sin repositorio'    => ['https://github.com/ejemplo'],
            'con subruta'        => ['https://github.com/ejemplo/repo/tree/main'],
            'con parámetros'     => ['https://github.com/ejemplo/repo?tab=readme'],
            'con barra final'    => ['https://github.com/ejemplo/repo/'],
            'termina en .git'    => ['https://github.com/ejemplo/repo.git'],
            'ruta hacia atrás'   => ['https://github.com/ejemplo/..'],
            'con salto de línea' => ["https://github.com/ejemplo/repo\nhttps://malo.com"],
            'vacío'              => [''],
        ];
    }

    /**
     * Cambiar el link borra la evaluación anterior, pero no devuelve los intentos.
     */
    public function test_cambiar_el_link_borra_la_evaluacion_y_conserva_los_intentos(): void
    {
        $entrega = $this->crearEntregaEvaluada();
        Sanctum::actingAs($this->estudiante);

        $this->guardar(self::OTRO_REPO)
            ->assertOk()
            ->assertJsonPath('entrega.estado', 'pendiente')
            ->assertJsonPath('entrega.puntaje', null)
            ->assertJsonPath('entrega.evaluacion', null)
            ->assertJsonPath('entrega.commit_sha', null)
            ->assertJsonPath('entrega.evaluado_en', null)
            ->assertJsonPath('entrega.intentos', 2)
            ->assertJsonPath('entrega.intentos_hoy', 2);

        $this->assertNotNull($entrega->fresh()->fecha_intentos);
    }

    /**
     * Guardar el mismo link no toca la evaluación.
     */
    public function test_guardar_el_mismo_link_conserva_la_evaluacion(): void
    {
        $this->crearEntregaEvaluada();
        Sanctum::actingAs($this->estudiante);

        $this->guardar(self::REPO)
            ->assertOk()
            ->assertJsonPath('entrega.estado', 'evaluado')
            ->assertJsonPath('entrega.puntaje', 74);
    }

    /**
     * Mientras se evalúa no se puede cambiar el link.
     */
    public function test_no_se_cambia_el_link_durante_la_evaluacion(): void
    {
        $entrega = $this->crearEntregaEvaluada();
        $entrega->update(['estado' => 'evaluando']);
        Sanctum::actingAs($this->estudiante);

        $this->guardar(self::OTRO_REPO)->assertStatus(409);

        $this->assertSame(self::REPO, $entrega->fresh()->repo_url);
    }

    /**
     * Una evaluación caída no deja la entrega bloqueada para siempre.
     */
    public function test_una_evaluacion_caida_permite_cambiar_el_link(): void
    {
        $entrega = $this->crearEntregaEvaluada();
        $entrega->update(['estado' => 'evaluando']);
        $this->travel(6)->minutes();
        Sanctum::actingAs($this->estudiante);

        $this->guardar(self::OTRO_REPO)->assertOk()->assertJsonPath('entrega.estado', 'pendiente');
    }

    /**
     * El instructor no entrega repositorios.
     */
    public function test_el_instructor_no_tiene_entrega(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->guardar(self::REPO)->assertForbidden();
        $this->consultar()->assertForbidden();
    }

    /**
     * Un estudiante de otro grupo no puede entregar ni consultar.
     */
    public function test_estudiante_ajeno_recibe_403(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));

        $this->guardar(self::REPO)->assertForbidden();
        $this->consultar()->assertForbidden();

        $this->assertSame(0, EntregaMiniProyecto::count());
    }

    /**
     * No se puede entregar en un mini proyecto de una semana bloqueada.
     */
    public function test_semana_bloqueada_recibe_403(): void
    {
        $this->proyecto = $this->crearProyecto($this->proyecto->guia, 2);
        Sanctum::actingAs($this->estudiante);

        $this->guardar(self::REPO)
            ->assertForbidden()
            ->assertJsonPath('message', 'Esta semana aún no está disponible.');
    }

    /**
     * En una guía personal solo entrega su dueño.
     */
    public function test_guia_personal_solo_la_entrega_su_dueno(): void
    {
        $guia = Guia::create(['nombre' => 'Mía', 'origen' => 'personal', 'usuario_id' => $this->estudiante->id]);
        $this->proyecto = $this->crearProyecto($guia, 5);

        Sanctum::actingAs($this->estudiante);
        $this->guardar(self::REPO)->assertCreated();

        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));
        $this->guardar(self::REPO)->assertForbidden();
    }

    /**
     * Pide guardar el repositorio del mini proyecto de prueba.
     */
    private function guardar(string $link)
    {
        return $this->putJson("/api/mini-proyectos/{$this->proyecto->id}/entrega", ['repo_url' => $link]);
    }

    /**
     * Pide la entrega del mini proyecto de prueba.
     */
    private function consultar()
    {
        return $this->getJson("/api/mini-proyectos/{$this->proyecto->id}/entrega");
    }

    /**
     * Crea una entrega que ya fue evaluada dos veces.
     */
    private function crearEntregaEvaluada(): EntregaMiniProyecto
    {
        return $this->proyecto->entregas()->create([
            'estudiante_id'  => $this->estudiante->id,
            'repo_url'       => self::REPO,
            'commit_sha'     => str_repeat('a', 40),
            'estado'         => 'evaluado',
            'puntaje'        => 74,
            'evaluacion'     => ['resumen' => 'Cumple lo principal.'],
            'intentos'       => 2,
            'intentos_hoy'   => 2,
            'fecha_intentos' => now(),
            'evaluado_en'    => now(),
        ]);
    }

    /**
     * Crea una semana con un mini proyecto.
     */
    private function crearProyecto(Guia $guia, int $numero): MiniProyecto
    {
        $semana = $guia->semanas()->create([
            'numero_semana'     => $numero,
            'titulo'            => "Semana {$numero}",
            'resumen'           => 'Resumen.',
            'contenido_texto'   => 'Texto',
            'hash_contenido'    => hash('sha256', 'Texto'),
            'estado_generacion' => 'listo',
        ]);

        return $semana->miniProyectos()->create([
            'guia_id'                => $guia->id,
            'orden'                  => 1,
            'titulo'                 => 'Calculadora',
            'descripcion'            => 'Descripción',
            'requisitos_funcionales' => ['Requisito'],
            'buenas_practicas'       => ['Práctica'],
            'criterios_evaluacion'   => [['criterio' => 'Criterio', 'peso' => 100]],
        ]);
    }
}
