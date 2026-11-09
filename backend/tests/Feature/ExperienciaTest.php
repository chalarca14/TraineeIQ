<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Models\MiniProyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Modo Personal: las semanas se abren con experiencia (XP). La semana siguiente se
 * abre cuando la anterior llega al 80 % de su XP máximo. En Modo Grupo no cambia nada.
 */
class ExperienciaTest extends TestCase
{
    use RefreshDatabase;

    private User $estudiante;
    private Guia $guia;

    /**
     * Crea una guía personal de 3 semanas, cada una con 3 mini proyectos.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->estudiante = User::factory()->create(['rol' => 'estudiante']);
        $this->guia = Guia::create(['nombre' => 'Mía', 'origen' => 'personal', 'usuario_id' => $this->estudiante->id]);

        foreach ([1, 2, 3] as $numero) {
            $this->crearSemana($this->guia, $numero, 3);
        }

        Sanctum::actingAs($this->estudiante);
    }

    /**
     * Sin experiencia solo está abierta la semana 1, y lo bloqueado responde 403.
     */
    public function test_sin_experiencia_solo_esta_abierta_la_semana_1(): void
    {
        $this->getJson("/api/guias/{$this->guia->id}/semanas")
            ->assertOk()
            ->assertJsonPath('semanas.0.disponible', true)
            ->assertJsonPath('semanas.0.xp_obtenido', 0)
            ->assertJsonPath('semanas.0.xp_maximo', 300)
            ->assertJsonPath('semanas.0.xp_requerido', 240)
            ->assertJsonPath('semanas.1.disponible', false)
            ->assertJsonPath('semanas.2.disponible', false);

        $proyecto = $this->proyecto(2, 1);

        $this->getJson("/api/guias/{$this->guia->id}/semanas/2")->assertForbidden();
        $this->getJson("/api/mini-proyectos/{$proyecto->id}")->assertForbidden();
        $this->getJson("/api/mini-proyectos/{$proyecto->id}/entrega")->assertForbidden();
        $this->putJson("/api/mini-proyectos/{$proyecto->id}/entrega", ['repo_url' => 'https://github.com/a/b'])
            ->assertForbidden();
    }

    /**
     * Con 239 XP la semana 2 sigue cerrada; con 240 se abre.
     */
    public function test_la_semana_siguiente_se_abre_con_el_80_por_ciento(): void
    {
        $this->darExperiencia(1, [100, 100, 39]);
        $this->getJson("/api/guias/{$this->guia->id}/semanas/2")->assertForbidden();

        $this->darExperiencia(1, [100, 100, 40]);
        $this->getJson("/api/guias/{$this->guia->id}/semanas/2")->assertOk();
        $this->getJson("/api/guias/{$this->guia->id}/semanas")->assertJsonPath('semanas.0.xp_obtenido', 240);
    }

    /**
     * La semana 3 exige completar la 2, aunque la 3 ya tenga experiencia.
     */
    public function test_las_semanas_se_abren_en_orden(): void
    {
        $this->darExperiencia(1, [100, 100, 100]);
        $this->darExperiencia(3, [100, 100, 100]);

        $this->getJson("/api/guias/{$this->guia->id}/semanas/2")->assertOk();
        $this->getJson("/api/guias/{$this->guia->id}/semanas/3")->assertForbidden();

        $this->darExperiencia(2, [80, 80, 80]);
        $this->getJson("/api/guias/{$this->guia->id}/semanas/3")->assertOk();
    }

    /**
     * Reevaluar con menos puntaje no baja la experiencia, y cambiar el link tampoco.
     */
    public function test_la_experiencia_nunca_baja(): void
    {
        $proyecto = $this->proyecto(1, 1);
        $this->fingirServicios([90, 50]);

        $this->putJson("/api/mini-proyectos/{$proyecto->id}/entrega", ['repo_url' => 'https://github.com/a/b'])->assertCreated();
        $this->postJson("/api/mini-proyectos/{$proyecto->id}/entrega/evaluar")->assertOk()->assertJsonPath('entrega.puntaje', 90);
        $this->postJson("/api/mini-proyectos/{$proyecto->id}/entrega/evaluar")
            ->assertOk()
            ->assertJsonPath('entrega.puntaje', 50)
            ->assertJsonPath('entrega.mejor_puntaje', 90);

        $this->putJson("/api/mini-proyectos/{$proyecto->id}/entrega", ['repo_url' => 'https://github.com/a/otro'])
            ->assertOk()
            ->assertJsonPath('entrega.puntaje', null)
            ->assertJsonPath('entrega.mejor_puntaje', 90);

        $this->getJson("/api/guias/{$this->guia->id}/semanas")->assertJsonPath('semanas.0.xp_obtenido', 90);
    }

    /**
     * Una semana sin mini proyectos (por ejemplo, con error al generarse) detiene el avance.
     */
    public function test_una_semana_sin_mini_proyectos_bloquea_la_siguiente(): void
    {
        $guia = Guia::create(['nombre' => 'Otra', 'origen' => 'personal', 'usuario_id' => $this->estudiante->id]);
        $this->crearSemana($guia, 1, 0, 'error');
        $this->crearSemana($guia, 2, 3);

        $this->getJson("/api/guias/{$guia->id}/semanas")
            ->assertOk()
            ->assertJsonPath('semanas.0.xp_maximo', 0)
            ->assertJsonPath('semanas.1.disponible', false);
    }

    /**
     * El total suma solo las guías personales; el instructor no tiene experiencia.
     */
    public function test_la_experiencia_total_solo_cuenta_el_modo_personal(): void
    {
        $this->darExperiencia(1, [90, 50, 0]);

        $instructor = User::factory()->create(['rol' => 'instructor']);
        $grupo = Grupo::create(['nombre' => 'ADSO', 'instructor_id' => $instructor->id, 'codigo_acceso' => 'ABC123']);
        $grupo->estudiantes()->attach($this->estudiante->id);
        $guiaGrupo = Guia::create(['nombre' => 'Del grupo', 'origen' => 'grupo', 'grupo_id' => $grupo->id]);
        $semanaGrupo = $this->crearSemana($guiaGrupo, 1, 1);
        $this->crearEntrega($semanaGrupo->miniProyectos()->first(), 100);

        $this->getJson('/api/usuario/xp')->assertOk()->assertJsonPath('xp_total', 140);

        Sanctum::actingAs($instructor);
        $this->getJson('/api/usuario/xp')->assertForbidden();
    }

    /**
     * En Modo Grupo no hay experiencia: las semanas las sigue liberando el instructor.
     */
    public function test_el_modo_grupo_no_cambia(): void
    {
        $instructor = User::factory()->create(['rol' => 'instructor']);
        $grupo = Grupo::create([
            'nombre'        => 'ADSO',
            'instructor_id' => $instructor->id,
            'codigo_acceso' => 'ABC123',
            'semana_actual' => 1,
        ]);
        $grupo->estudiantes()->attach($this->estudiante->id);
        $guia = Guia::create(['nombre' => 'Del grupo', 'origen' => 'grupo', 'grupo_id' => $grupo->id]);
        $semana = $this->crearSemana($guia, 1, 3);
        $this->crearSemana($guia, 2, 3);

        // Ni con toda la experiencia de la semana 1 se abre la 2
        foreach ($semana->miniProyectos as $proyecto) {
            $this->crearEntrega($proyecto, 100);
        }

        $this->getJson("/api/guias/{$guia->id}/semanas")
            ->assertOk()
            ->assertJsonMissingPath('semanas.0.xp_obtenido')
            ->assertJsonPath('semanas.1.disponible', false);

        $grupo->update(['semana_actual' => 2]);
        $this->getJson("/api/guias/{$guia->id}/semanas/2")->assertOk();
    }

    /**
     * La experiencia de todas las semanas se calcula con una sola consulta.
     */
    public function test_la_experiencia_se_calcula_con_una_sola_consulta(): void
    {
        DB::enableQueryLog();

        $this->getJson("/api/guias/{$this->guia->id}/semanas")->assertOk();

        $consultas = collect(DB::getQueryLog())->filter(fn ($consulta) => str_contains($consulta['query'], 'mejor_puntaje'));

        $this->assertCount(1, $consultas);
    }

    /**
     * Fija el mejor puntaje del estudiante en cada mini proyecto de una semana.
     */
    private function darExperiencia(int $numeroSemana, array $puntajes): void
    {
        foreach ($puntajes as $indice => $puntaje) {
            $proyecto = $this->proyecto($numeroSemana, $indice + 1);
            $proyecto->entregas()->updateOrCreate(
                ['estudiante_id' => $this->estudiante->id],
                ['repo_url' => 'https://github.com/a/b', 'estado' => 'evaluado', 'puntaje' => $puntaje, 'mejor_puntaje' => $puntaje]
            );
        }
    }

    /**
     * Crea una entrega evaluada del estudiante en un mini proyecto.
     */
    private function crearEntrega(MiniProyecto $proyecto, int $puntaje): void
    {
        $proyecto->entregas()->create([
            'estudiante_id' => $this->estudiante->id,
            'repo_url'      => 'https://github.com/a/b',
            'estado'        => 'evaluado',
            'puntaje'       => $puntaje,
            'mejor_puntaje' => $puntaje,
        ]);
    }

    /**
     * Devuelve un mini proyecto de la guía personal por semana y orden.
     */
    private function proyecto(int $numeroSemana, int $orden): MiniProyecto
    {
        return $this->guia->semanas()->where('numero_semana', $numeroSemana)->firstOrFail()
            ->miniProyectos()->where('orden', $orden)->firstOrFail();
    }

    /**
     * Crea una semana con la cantidad indicada de mini proyectos.
     */
    private function crearSemana(Guia $guia, int $numero, int $proyectos, string $estado = 'listo'): GuiaSemana
    {
        $semana = $guia->semanas()->create([
            'numero_semana'     => $numero,
            'titulo'            => "Semana {$numero}",
            'resumen'           => 'Resumen.',
            'contenido_texto'   => "Texto {$numero}",
            'hash_contenido'    => hash('sha256', "Texto {$numero}"),
            'estado_generacion' => $estado,
        ]);

        for ($orden = 1; $orden <= $proyectos; $orden++) {
            $semana->miniProyectos()->create([
                'guia_id'                => $guia->id,
                'orden'                  => $orden,
                'titulo'                 => "Proyecto {$orden}",
                'descripcion'            => 'Descripción',
                'requisitos_funcionales' => ['Requisito'],
                'buenas_practicas'       => ['Práctica'],
                'criterios_evaluacion'   => [['criterio' => 'Funciona', 'peso' => 100]],
            ]);
        }

        return $semana;
    }

    /**
     * Simula GitHub (un commit distinto por evaluación) y a Gemini con los puntajes indicados.
     */
    private function fingirServicios(array $puntajes): void
    {
        $commit = 0;
        $evaluacion = 0;

        Http::fake([
            'api.github.com/*' => function (Request $peticion) use (&$commit) {
                $url = $peticion->url();

                if (str_contains($url, '/git/blobs/')) {
                    return Http::response('function sumar(a, b) { return a + b }');
                }

                if (str_contains($url, '/git/trees/')) {
                    return Http::response(['truncated' => false, 'tree' => [
                        ['path' => 'index.js', 'type' => 'blob', 'sha' => 'blob1', 'size' => 40],
                    ]]);
                }

                if (str_contains($url, '/commits/')) {
                    $commit++;

                    return Http::response(['sha' => str_repeat((string) $commit, 40), 'commit' => ['tree' => ['sha' => 'arbol1']]]);
                }

                return Http::response(['private' => false, 'default_branch' => 'main']);
            },
            'generativelanguage.googleapis.com/*' => function () use (&$evaluacion, $puntajes) {
                $cumplimiento = $puntajes[$evaluacion++];

                $datos = [
                    'resumen'          => 'Evaluación.',
                    'criterios'        => [['criterio' => 'Funciona', 'cumplimiento' => $cumplimiento, 'comentario' => 'Bien.']],
                    'requisitos'       => [['requisito' => 'Requisito', 'cumple' => true, 'comentario' => 'Sí.']],
                    'buenas_practicas' => [['practica' => 'Práctica', 'cumple' => true, 'comentario' => 'Sí.']],
                    'sugerencias'      => [],
                ];

                return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($datos)]]]]]]);
            },
        ]);
    }
}
