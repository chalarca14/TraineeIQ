<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Editar el nombre y la descripción de un grupo: PATCH /api/grupos/{id}.
 */
class EditarGrupoTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private Grupo $grupo;

    /**
     * Cada prueba parte de un grupo con su instructor dueño.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['rol' => 'instructor']);
        $this->grupo = Grupo::create([
            'nombre'        => 'ADSO',
            'descripcion'   => 'Descripción vieja',
            'instructor_id' => $this->instructor->id,
            'codigo_acceso' => 'ABC123',
            'semana_actual' => 3,
        ]);
    }

    /**
     * El instructor dueño cambia el nombre y la descripción, y la respuesta trae el grupo nuevo.
     */
    public function test_el_instructor_dueno_edita_su_grupo(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->patchJson("/api/grupos/{$this->grupo->id}", [
            'nombre'      => 'ADSO – Ficha 2873645',
            'descripcion' => 'Análisis y Desarrollo de Software, jornada mañana',
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'ADSO – Ficha 2873645')
            ->assertJsonPath('data.descripcion', 'Análisis y Desarrollo de Software, jornada mañana');

        $this->assertSame('ADSO – Ficha 2873645', $this->grupo->fresh()->nombre);
    }

    /**
     * Editar no cambia el código de acceso, la semana liberada ni el dueño, aunque se envíen.
     */
    public function test_editar_no_toca_codigo_semana_ni_instructor(): void
    {
        Sanctum::actingAs($this->instructor);
        $otro = User::factory()->create(['rol' => 'instructor']);

        $this->patchJson("/api/grupos/{$this->grupo->id}", [
            'nombre'        => 'Nuevo nombre',
            'codigo_acceso' => 'ZZZ999',
            'semana_actual' => 10,
            'instructor_id' => $otro->id,
        ])->assertOk();

        $grupo = $this->grupo->fresh();
        $this->assertSame('ABC123', $grupo->codigo_acceso);
        $this->assertSame(3, $grupo->semana_actual);
        $this->assertSame($this->instructor->id, $grupo->instructor_id);
    }

    /**
     * Dejar la descripción vacía la borra.
     */
    public function test_se_puede_borrar_la_descripcion(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->patchJson("/api/grupos/{$this->grupo->id}", ['nombre' => 'ADSO', 'descripcion' => ''])
            ->assertOk()
            ->assertJsonPath('data.descripcion', null);
    }

    /**
     * Si la descripción no se envía, se conserva la que tenía.
     */
    public function test_sin_descripcion_se_conserva_la_anterior(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->patchJson("/api/grupos/{$this->grupo->id}", ['nombre' => 'Solo cambio el nombre'])
            ->assertOk()
            ->assertJsonPath('data.descripcion', 'Descripción vieja');
    }

    /**
     * Nombre vacío (o solo espacios) y textos demasiado largos responden 422 en español.
     */
    public function test_valida_nombre_y_descripcion(): void
    {
        Sanctum::actingAs($this->instructor);
        $url = "/api/grupos/{$this->grupo->id}";

        $this->patchJson($url, ['nombre' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre' => 'Escribe el nombre del grupo.']);

        $this->patchJson($url, ['nombre' => 'ADSO', 'descripcion' => str_repeat('a', 1001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['descripcion' => 'La descripción es demasiado larga (máximo 1000 caracteres).']);

        $this->assertSame('ADSO', $this->grupo->fresh()->nombre);
    }

    /**
     * Otro instructor no puede editar un grupo ajeno.
     */
    public function test_otro_instructor_no_puede_editar(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'instructor']));

        $this->patchJson("/api/grupos/{$this->grupo->id}", ['nombre' => 'Robado'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo el instructor del grupo puede modificarlo.');

        $this->assertSame('ADSO', $this->grupo->fresh()->nombre);
    }

    /**
     * Un estudiante, aunque esté inscrito, no puede editar el grupo.
     */
    public function test_un_estudiante_no_puede_editar(): void
    {
        $estudiante = User::factory()->create(['rol' => 'estudiante']);
        $this->grupo->estudiantes()->attach($estudiante->id);
        Sanctum::actingAs($estudiante);

        $this->patchJson("/api/grupos/{$this->grupo->id}", ['nombre' => 'Cambiado'])->assertForbidden();

        $this->assertSame('ADSO', $this->grupo->fresh()->nombre);
    }

    /**
     * Un grupo que no existe responde 404.
     */
    public function test_grupo_inexistente_responde_404(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->patchJson('/api/grupos/999', ['nombre' => 'ADSO'])->assertNotFound();
    }
}
