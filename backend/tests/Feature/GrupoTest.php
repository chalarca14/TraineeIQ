<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GrupoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El instructor crea un grupo y queda registrado como su dueño.
     */
    public function test_instructor_puede_crear_y_listar_sus_grupos(): void
    {
        $instructor = User::factory()->create(['rol' => 'instructor']);
        Sanctum::actingAs($instructor);

        $this->postJson('/api/grupos', ['nombre' => 'ADSO 2026'])
            ->assertCreated()
            ->assertJsonPath('data.instructor_id', $instructor->id);

        $this->getJson('/api/grupos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'ADSO 2026');
    }

    /**
     * Un estudiante no puede crear grupos.
     */
    public function test_estudiante_no_puede_crear_grupos(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));

        $this->postJson('/api/grupos', ['nombre' => 'Grupo no permitido'])
            ->assertForbidden();
    }

    /**
     * El estudiante se une con el código y ve el grupo con su instructor.
     */
    public function test_estudiante_se_une_con_codigo_y_ve_el_grupo(): void
    {
        $instructor = User::factory()->create(['rol' => 'instructor']);
        $grupo = $this->crearGrupo($instructor);

        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));

        $this->postJson('/api/grupos/unirse', ['codigo_acceso' => $grupo->codigo_acceso])
            ->assertOk();

        $this->getJson('/api/grupos')
            ->assertOk()
            ->assertJsonPath('data.0.id', $grupo->id)
            ->assertJsonPath('data.0.instructor.id', $instructor->id);

        $this->getJson("/api/grupos/{$grupo->id}")
            ->assertOk()
            ->assertJsonPath('data.instructor.id', $instructor->id);
    }

    /**
     * El instructor ya no recibe 403 al consultar guías de su propio grupo,
     * y sigue sin poder subir guías a un grupo ajeno.
     */
    public function test_instructor_solo_sube_guias_a_sus_grupos(): void
    {
        Storage::fake('public');
        Http::fake();

        $instructor = User::factory()->create(['rol' => 'instructor']);
        $grupoAjeno = $this->crearGrupo(User::factory()->create(['rol' => 'instructor']));

        Sanctum::actingAs($instructor);

        $this->getJson('/api/guias')->assertOk();

        $this->post('/api/guias', [
            'nombre'   => 'Guía ajena',
            'archivo'  => UploadedFile::fake()->create('guia.pdf', 10, 'application/pdf'),
            'grupo_id' => $grupoAjeno->id,
        ], ['Accept' => 'application/json'])->assertForbidden();
    }

    /**
     * Crea un grupo de prueba para el instructor indicado.
     */
    private function crearGrupo(User $instructor): Grupo
    {
        return Grupo::create([
            'nombre'        => 'Grupo de prueba',
            'instructor_id' => $instructor->id,
            'codigo_acceso' => 'ABC123',
        ]);
    }
}
