<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\Guia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EntregasGrupoTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private User $estudiante;
    private Grupo $grupo;

    /**
     * Crea un grupo con una entrega evaluada de su estudiante.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['rol' => 'instructor']);
        $this->estudiante = User::factory()->create(['rol' => 'estudiante']);
        $this->grupo = $this->crearGrupo($this->instructor, 'ABC123');
        $this->grupo->estudiantes()->attach($this->estudiante->id);

        $guia = Guia::create(['nombre' => 'Guía del grupo', 'origen' => 'grupo', 'grupo_id' => $this->grupo->id]);
        $this->crearEntrega($guia, $this->estudiante, 80);
    }

    /**
     * El instructor ve quién entregó, qué repositorio y qué puntaje sacó.
     */
    public function test_el_instructor_ve_las_entregas_de_su_grupo(): void
    {
        Sanctum::actingAs($this->instructor);

        $respuesta = $this->pedirEntregas()
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.puntaje', 80)
            ->assertJsonPath('data.0.repo_url', 'https://github.com/ejemplo/repo')
            ->assertJsonPath('data.0.estudiante.name', $this->estudiante->name)
            ->assertJsonPath('data.0.mini_proyecto.titulo', 'Calculadora')
            ->assertJsonPath('data.0.mini_proyecto.guia.nombre', 'Guía del grupo')
            ->assertJsonPath('data.0.mini_proyecto.semana.numero_semana', 1);

        $this->assertArrayNotHasKey('evaluacion', $respuesta->json('data.0'));
    }

    /**
     * Las entregas del Modo Personal y las de otros grupos nunca aparecen.
     */
    public function test_no_incluye_guias_personales_ni_de_otros_grupos(): void
    {
        $personal = Guia::create(['nombre' => 'Mía', 'origen' => 'personal', 'usuario_id' => $this->estudiante->id]);
        $this->crearEntrega($personal, $this->estudiante, 100);

        $otroGrupo = $this->crearGrupo(User::factory()->create(['rol' => 'instructor']), 'XYZ789');
        $otraGuia = Guia::create(['nombre' => 'Ajena', 'origen' => 'grupo', 'grupo_id' => $otroGrupo->id]);
        $this->crearEntrega($otraGuia, $this->estudiante, 90);

        Sanctum::actingAs($this->instructor);

        $this->pedirEntregas()->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.puntaje', 80);
    }

    /**
     * Ni otro instructor ni los estudiantes del grupo pueden ver la lista.
     */
    public function test_solo_el_instructor_dueno_tiene_acceso(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'instructor']));
        $this->pedirEntregas()->assertForbidden();

        Sanctum::actingAs($this->estudiante);
        $this->pedirEntregas()->assertForbidden();
    }

    /**
     * Un grupo que no existe responde 404.
     */
    public function test_grupo_inexistente_responde_404(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->getJson('/api/grupos/9999/entregas')->assertNotFound();
    }

    /**
     * Los filtros por mini proyecto, estudiante y estado reducen la lista y se suman entre si.
     */
    public function test_filtra_por_mini_proyecto_estudiante_y_estado(): void
    {
        $otro = User::factory()->create(['rol' => 'estudiante']);
        $this->grupo->estudiantes()->attach($otro->id);
        $guia = Guia::create(['nombre' => 'Segunda', 'origen' => 'grupo', 'grupo_id' => $this->grupo->id]);
        $this->crearEntrega($guia, $otro, 50, 'error');

        $proyectoOtro = $guia->miniProyectos()->first()->id;
        Sanctum::actingAs($this->instructor);

        $this->pedirEntregas()->assertJsonCount(2, 'data');
        $this->pedirEntregas("?mini_proyecto_id={$proyectoOtro}")->assertJsonCount(1, 'data')->assertJsonPath('data.0.puntaje', 50);
        $this->pedirEntregas("?estudiante_id={$this->estudiante->id}")->assertJsonCount(1, 'data')->assertJsonPath('data.0.puntaje', 80);
        $this->pedirEntregas('?estado=evaluado')->assertJsonCount(1, 'data')->assertJsonPath('data.0.puntaje', 80);
        $this->pedirEntregas("?estado=evaluado&estudiante_id={$otro->id}")->assertJsonCount(0, 'data');
    }

    /**
     * Un filtro con un valor que no corresponde responde 422.
     */
    public function test_filtro_invalido_responde_422(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->pedirEntregas('?estado=aprobado')->assertStatus(422)->assertJsonValidationErrors('estado');
        $this->pedirEntregas('?mini_proyecto_id=abc')->assertStatus(422)->assertJsonValidationErrors('mini_proyecto_id');
    }

    /**
     * Pide las entregas del grupo principal, con filtros opcionales en la query.
     */
    private function pedirEntregas(string $query = '')
    {
        return $this->getJson("/api/grupos/{$this->grupo->id}/entregas{$query}");
    }

    /**
     * Crea un grupo del instructor indicado.
     */
    private function crearGrupo(User $instructor, string $codigo): Grupo
    {
        return Grupo::create(['nombre' => 'ADSO', 'instructor_id' => $instructor->id, 'codigo_acceso' => $codigo]);
    }

    /**
     * Crea una semana con un mini proyecto y la entrega evaluada del estudiante.
     */
    private function crearEntrega(Guia $guia, User $estudiante, int $puntaje, string $estado = 'evaluado'): void
    {
        $semana = $guia->semanas()->create([
            'numero_semana'     => 1,
            'titulo'            => 'Semana 1',
            'resumen'           => 'Resumen.',
            'contenido_texto'   => 'Texto',
            'hash_contenido'    => hash('sha256', 'Texto'),
            'estado_generacion' => 'listo',
        ]);

        $proyecto = $semana->miniProyectos()->create([
            'guia_id'                => $guia->id,
            'orden'                  => 1,
            'titulo'                 => 'Calculadora',
            'descripcion'            => 'Descripción',
            'requisitos_funcionales' => ['Suma dos números'],
            'buenas_practicas'       => ['Usa nombres descriptivos'],
            'criterios_evaluacion'   => [['criterio' => 'Funciona', 'peso' => 100]],
        ]);

        $proyecto->entregas()->create([
            'estudiante_id' => $estudiante->id,
            'repo_url'      => 'https://github.com/ejemplo/repo',
            'estado'        => $estado,
            'puntaje'       => $puntaje,
            'evaluacion'    => ['resumen' => 'Detalle que la lista no debe traer.'],
        ]);
    }
}
