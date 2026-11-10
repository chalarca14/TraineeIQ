<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\Guia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Datos que piden las pantallas del instructor: la ultima semana del grupo
 * y la lista de guias filtrada por grupo.
 */
class PantallasInstructorTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private Grupo $grupo;
    private Grupo $otroGrupo;

    /**
     * Crea un instructor con dos grupos.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['rol' => 'instructor']);
        $this->grupo = $this->crearGrupo($this->instructor, 'ABC123');
        $this->otroGrupo = $this->crearGrupo($this->instructor, 'DEF456');
    }

    /**
     * ultima_semana es la mayor cantidad de semanas entre las guias del grupo.
     */
    public function test_el_grupo_trae_su_ultima_semana(): void
    {
        $this->crearGuia($this->grupo, 'Corta', 4);
        $this->crearGuia($this->grupo, 'Larga', 6);
        $this->crearGuia($this->otroGrupo, 'De otro grupo', 10);

        Sanctum::actingAs($this->instructor);

        $this->getJson("/api/grupos/{$this->grupo->id}")
            ->assertOk()
            ->assertJsonPath('data.ultima_semana', 6);
    }

    /**
     * Un grupo sin guias responde ultima_semana en null.
     */
    public function test_grupo_sin_guias_trae_ultima_semana_null(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->getJson("/api/grupos/{$this->grupo->id}")
            ->assertOk()
            ->assertJsonPath('data.ultima_semana', null);
    }

    /**
     * Con ?grupo_id= el instructor solo recibe las guias de ese grupo.
     */
    public function test_el_instructor_filtra_las_guias_por_grupo(): void
    {
        $this->crearGuia($this->grupo, 'Del grupo', 3);
        $this->crearGuia($this->otroGrupo, 'De otro grupo', 3);

        Sanctum::actingAs($this->instructor);

        $this->getJson('/api/guias')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/guias?grupo_id={$this->grupo->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Del grupo');
    }

    /**
     * El filtro nunca da acceso a guias de grupos ajenos.
     */
    public function test_el_filtro_no_muestra_guias_de_grupos_ajenos(): void
    {
        $ajeno = $this->crearGrupo(User::factory()->create(['rol' => 'instructor']), 'XYZ789');
        $this->crearGuia($ajeno, 'Ajena', 3);

        Sanctum::actingAs($this->instructor);

        $this->getJson("/api/guias?grupo_id={$ajeno->id}")->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/guias?grupo_id=abc')->assertStatus(422)->assertJsonValidationErrors('grupo_id');
    }

    /**
     * Crea un grupo del instructor indicado.
     */
    private function crearGrupo(User $instructor, string $codigo): Grupo
    {
        return Grupo::create(['nombre' => 'ADSO', 'instructor_id' => $instructor->id, 'codigo_acceso' => $codigo]);
    }

    /**
     * Crea una guia de grupo con la cantidad de semanas indicada.
     */
    private function crearGuia(Grupo $grupo, string $nombre, int $semanas): Guia
    {
        return Guia::create([
            'nombre'          => $nombre,
            'origen'          => 'grupo',
            'grupo_id'        => $grupo->id,
            'semanas_totales' => $semanas,
        ]);
    }
}
