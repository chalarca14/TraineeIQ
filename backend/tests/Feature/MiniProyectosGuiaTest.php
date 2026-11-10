<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * GET /api/guias/{id}/mini-proyectos: mini proyectos de las semanas abiertas,
 * cada uno con la entrega del usuario que pregunta.
 */
class MiniProyectosGuiaTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private User $estudiante;
    private User $companero;
    private Guia $guia;

    /**
     * Crea un grupo en la semana 1 de una guía de 2 semanas, con 3 mini proyectos por semana.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['rol' => 'instructor']);
        $this->estudiante = User::factory()->create(['rol' => 'estudiante']);
        $this->companero = User::factory()->create(['rol' => 'estudiante']);

        $grupo = Grupo::create([
            'nombre'        => 'ADSO',
            'instructor_id' => $this->instructor->id,
            'codigo_acceso' => 'ABC123',
            'semana_actual' => 1,
        ]);
        $grupo->estudiantes()->attach([$this->estudiante->id, $this->companero->id]);

        $this->guia = Guia::create(['nombre' => 'Guía', 'origen' => 'grupo', 'grupo_id' => $grupo->id]);
        $this->crearSemana($this->guia, 1);
        $this->crearSemana($this->guia, 2);
    }

    /**
     * El estudiante recibe solo los de semanas liberadas, con su entrega y no la de otro.
     */
    public function test_el_estudiante_recibe_los_abiertos_con_su_entrega(): void
    {
        $proyecto = $this->guia->miniProyectos()->where('orden', 1)->firstOrFail();
        $proyecto->entregas()->create(['estudiante_id' => $this->estudiante->id, 'repo_url' => 'https://github.com/a/mio', 'estado' => 'evaluado', 'puntaje' => 70, 'mejor_puntaje' => 85, 'intentos' => 2]);
        $proyecto->entregas()->create(['estudiante_id' => $this->companero->id, 'repo_url' => 'https://github.com/b/otro']);

        Sanctum::actingAs($this->estudiante);

        $respuesta = $this->pedir($this->guia)
            ->assertOk()
            ->assertJsonCount(3, 'mini_proyectos')
            ->assertJsonPath('mini_proyectos.0.semana.numero_semana', 1)
            ->assertJsonPath('mini_proyectos.0.entrega.repo_url', 'https://github.com/a/mio')
            ->assertJsonPath('mini_proyectos.0.entrega.puntaje', 70)
            ->assertJsonPath('mini_proyectos.0.entrega.mejor_puntaje', 85)
            ->assertJsonPath('mini_proyectos.0.entrega.intentos', 2)
            ->assertJsonPath('mini_proyectos.1.entrega', null);

        $this->assertStringNotContainsString('github.com/b/otro', $respuesta->getContent());
        $this->assertArrayNotHasKey('entregas', $respuesta->json('mini_proyectos.0'));
    }

    /**
     * Al liberar la semana 2 aparecen sus mini proyectos, en orden de semana.
     */
    public function test_liberar_una_semana_agrega_sus_mini_proyectos(): void
    {
        $this->guia->grupo->update(['semana_actual' => 2]);
        Sanctum::actingAs($this->estudiante);

        $this->pedir($this->guia)
            ->assertOk()
            ->assertJsonCount(6, 'mini_proyectos')
            ->assertJsonPath('mini_proyectos.3.semana.numero_semana', 2)
            ->assertJsonPath('mini_proyectos.3.orden', 1);
    }

    /**
     * El instructor ve todos los mini proyectos, sin entrega propia.
     */
    public function test_el_instructor_ve_todos_sin_entrega(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->pedir($this->guia)
            ->assertOk()
            ->assertJsonCount(6, 'mini_proyectos')
            ->assertJsonPath('mini_proyectos.0.entrega', null);
    }

    /**
     * En Modo Personal solo aparecen los de semanas abiertas con experiencia.
     */
    public function test_en_modo_personal_respeta_la_experiencia(): void
    {
        $personal = Guia::create(['nombre' => 'Mía', 'origen' => 'personal', 'usuario_id' => $this->estudiante->id]);
        $semana1 = $this->crearSemana($personal, 1);
        $this->crearSemana($personal, 2);

        Sanctum::actingAs($this->estudiante);
        $this->pedir($personal)->assertOk()->assertJsonCount(3, 'mini_proyectos');

        foreach ($semana1->miniProyectos as $proyecto) {
            $proyecto->entregas()->create(['estudiante_id' => $this->estudiante->id, 'repo_url' => 'https://github.com/a/b', 'mejor_puntaje' => 80]);
        }

        $this->pedir($personal)->assertOk()->assertJsonCount(6, 'mini_proyectos');
    }

    /**
     * Quien no tiene acceso a la guía recibe 403, y una guía que no existe, 404.
     */
    public function test_sin_acceso_responde_403_y_sin_guia_404(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));
        $this->pedir($this->guia)->assertForbidden();

        $this->getJson('/api/guias/9999/mini-proyectos')->assertNotFound();
    }

    /**
     * Pide los mini proyectos de una guía.
     */
    private function pedir(Guia $guia)
    {
        return $this->getJson("/api/guias/{$guia->id}/mini-proyectos");
    }

    /**
     * Crea una semana lista con 3 mini proyectos.
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

        foreach (['basico', 'intermedio', 'avanzado'] as $indice => $dificultad) {
            $semana->miniProyectos()->create([
                'guia_id'                => $guia->id,
                'orden'                  => $indice + 1,
                'titulo'                 => "Proyecto {$dificultad}",
                'descripcion'            => 'Descripción',
                'dificultad'             => $dificultad,
                'requisitos_funcionales' => ['Requisito'],
                'buenas_practicas'       => ['Práctica'],
                'criterios_evaluacion'   => [['criterio' => 'Funciona', 'peso' => 100]],
            ]);
        }

        return $semana;
    }
}
