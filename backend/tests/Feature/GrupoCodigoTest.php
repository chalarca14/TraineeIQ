<?php

namespace Tests\Feature;

use App\Models\EntregaMiniProyecto;
use App\Models\Grupo;
use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Models\MiniProyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Código de acceso y borrado de grupos. Regla de negocio: el código está activo
 * siempre, hasta que el instructor borre el grupo.
 */
class GrupoCodigoTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private User $estudiante;
    private Grupo $grupo;

    /**
     * Crea un grupo con un estudiante inscrito y una guía con una entrega evaluada.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->instructor = User::factory()->create(['rol' => 'instructor']);
        $this->estudiante = User::factory()->create(['rol' => 'estudiante']);

        Sanctum::actingAs($this->instructor);
        $this->grupo = Grupo::findOrFail($this->postJson('/api/grupos', ['nombre' => 'ADSO'])->assertCreated()->json('data.id'));
        $this->grupo->estudiantes()->attach($this->estudiante->id);

        Storage::disk('public')->put('guias/guia.pdf', '%PDF-1.4');
        $guia = Guia::create(['nombre' => 'Guía', 'origen' => 'grupo', 'grupo_id' => $this->grupo->id, 'archivo_pdf' => 'guias/guia.pdf']);

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
            'titulo'                 => 'Proyecto',
            'descripcion'            => 'Descripción',
            'requisitos_funcionales' => ['Requisito'],
            'buenas_practicas'       => ['Práctica'],
            'criterios_evaluacion'   => [['criterio' => 'Funciona', 'peso' => 100]],
        ]);

        $proyecto->entregas()->create(['estudiante_id' => $this->estudiante->id, 'repo_url' => 'https://github.com/a/b', 'puntaje' => 80]);
    }

    /**
     * El backend genera un código de 6 caracteres e ignora el que mande el cliente.
     */
    public function test_el_codigo_lo_genera_el_backend(): void
    {
        $codigo = $this->postJson('/api/grupos', ['nombre' => 'Otro', 'codigo_acceso' => 'MIO123'])
            ->assertCreated()
            ->json('data.codigo_acceso');

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $codigo);
        $this->assertNotSame('MIO123', $codigo);
    }

    /**
     * El instructor dueño borra el grupo y se borra todo lo que depende de él.
     */
    public function test_el_instructor_borra_su_grupo_con_todo_su_contenido(): void
    {
        $this->deleteJson("/api/grupos/{$this->grupo->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Grupo borrado correctamente');

        $this->assertDatabaseMissing('grupos', ['id' => $this->grupo->id]);
        $this->assertDatabaseMissing('estudiante_grupo', ['grupo_id' => $this->grupo->id]);
        $this->assertSame(0, Guia::count());
        $this->assertSame(0, GuiaSemana::count());
        $this->assertSame(0, MiniProyecto::count());
        $this->assertSame(0, EntregaMiniProyecto::count());
        Storage::disk('public')->assertMissing('guias/guia.pdf');

        // El estudiante sigue existiendo: solo pierde el grupo
        $this->assertDatabaseHas('users', ['id' => $this->estudiante->id]);
    }

    /**
     * Después de borrar el grupo, su código deja de funcionar.
     */
    public function test_el_codigo_deja_de_funcionar_al_borrar_el_grupo(): void
    {
        $codigo = $this->grupo->codigo_acceso;
        $this->deleteJson("/api/grupos/{$this->grupo->id}")->assertOk();

        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));

        $this->postJson('/api/grupos/unirse', ['codigo_acceso' => $codigo])
            ->assertUnprocessable()
            ->assertJsonPath('errors.codigo_acceso.0', 'Ese código no existe. Revisa que esté bien escrito.');
    }

    /**
     * Solo el instructor dueño puede borrar: ni otro instructor ni un estudiante.
     */
    public function test_solo_el_instructor_dueno_borra(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'instructor']));
        $this->deleteJson("/api/grupos/{$this->grupo->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo el instructor del grupo puede borrarlo.');

        Sanctum::actingAs($this->estudiante);
        $this->deleteJson("/api/grupos/{$this->grupo->id}")->assertForbidden();

        $this->assertDatabaseHas('grupos', ['id' => $this->grupo->id]);
    }

    /**
     * Borrar un grupo que no existe responde 404.
     */
    public function test_borrar_un_grupo_inexistente_responde_404(): void
    {
        $this->deleteJson('/api/grupos/9999')->assertNotFound();
    }

    /**
     * El código funciona con minúsculas y espacios, porque se guarda en mayúsculas.
     */
    public function test_el_codigo_acepta_minusculas_y_espacios(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));

        $this->postJson('/api/grupos/unirse', ['codigo_acceso' => '  ' . strtolower($this->grupo->codigo_acceso) . ' '])
            ->assertOk()
            ->assertJsonPath('data.id', $this->grupo->id);
    }

    /**
     * Mensajes en español para un código vacío o que no existe.
     */
    public function test_errores_del_codigo_en_espanol(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));

        $this->postJson('/api/grupos/unirse', ['codigo_acceso' => ''])
            ->assertUnprocessable()
            ->assertJsonPath('errors.codigo_acceso.0', 'Escribe el código del grupo.');

        $this->postJson('/api/grupos/unirse', ['codigo_acceso' => 'ZZZZZZ'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.codigo_acceso.0', 'Ese código no existe. Revisa que esté bien escrito.');
    }

    /**
     * Unirse dos veces responde 409 y no duplica la inscripción.
     */
    public function test_no_se_puede_inscribir_dos_veces(): void
    {
        Sanctum::actingAs($this->estudiante);

        $this->postJson('/api/grupos/unirse', ['codigo_acceso' => $this->grupo->codigo_acceso])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Ya estás inscrito en este grupo.');

        $this->assertSame(1, $this->grupo->estudiantes()->count());
    }

    /**
     * Un instructor no puede unirse a un grupo con el código.
     */
    public function test_un_instructor_no_puede_unirse(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'instructor']));

        $this->postJson('/api/grupos/unirse', ['codigo_acceso' => $this->grupo->codigo_acceso])->assertForbidden();
    }
}
