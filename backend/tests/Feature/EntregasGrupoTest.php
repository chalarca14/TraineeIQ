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
     * Pide las entregas del grupo principal.
     */
    private function pedirEntregas()
    {
        return $this->getJson("/api/grupos/{$this->grupo->id}/entregas");
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
    private function crearEntrega(Guia $guia, User $estudiante, int $puntaje): void
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
            'estado'        => 'evaluado',
            'puntaje'       => $puntaje,
            'evaluacion'    => ['resumen' => 'Detalle que la lista no debe traer.'],
        ]);
    }
}
