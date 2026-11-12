<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\Guia;
use App\Models\MiniProyecto;
use App\Models\Notificacion;
use App\Models\User;
use App\Services\NotificacionService;
use App\Services\ProcesarGuiaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    private const REPO = 'https://github.com/ejemplo/calculadora';

    private User $instructor;
    private User $estudiante;
    private MiniProyecto $proyecto;

    // Commit que devuelve el GitHub simulado; cambiarlo simula un push nuevo
    private string $sha = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    /**
     * Crea un grupo con una guía de una semana, un mini proyecto y la entrega del estudiante.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['rol' => 'instructor']);
        $this->estudiante = User::factory()->create(['rol' => 'estudiante', 'name' => 'Ana Pérez']);

        $grupo = Grupo::create([
            'nombre'        => 'ADSO',
            'instructor_id' => $this->instructor->id,
            'codigo_acceso' => 'ABC123',
            'semana_actual' => 1,
        ]);
        $grupo->estudiantes()->attach($this->estudiante->id);

        $guia = Guia::create(['nombre' => 'Fundamentos JS', 'origen' => 'grupo', 'grupo_id' => $grupo->id]);
        $this->proyecto = $this->crearProyecto($guia);
        $this->guardarRepo($this->estudiante, $this->proyecto);

        $this->fingirServicios();
    }

    /**
     * Evaluar en Modo Grupo deja un aviso al instructor con el puntaje.
     */
    public function test_evaluar_avisa_al_instructor(): void
    {
        $this->evaluar()->assertOk();

        Sanctum::actingAs($this->instructor);

        $this->getJson('/api/notificaciones')
            ->assertOk()
            ->assertJsonPath('no_leidas', 1)
            ->assertJsonPath(
                'notificaciones.data.0.mensaje',
                'Ana Pérez terminó el mini proyecto «Calculadora» (básico, semana 1 de «Fundamentos JS») con 74/100.'
            )
            ->assertJsonPath('notificaciones.data.0.datos.puntaje', 74)
            ->assertJsonPath('notificaciones.data.0.datos.grupo', 'ADSO')
            ->assertJsonPath('notificaciones.data.0.leida_en', null);
    }

    /**
     * Repetir el mismo commit no gasta intento y tampoco repite el aviso.
     */
    public function test_el_mismo_commit_no_repite_el_aviso(): void
    {
        $this->evaluar()->assertOk();
        $primera = Notificacion::firstOrFail();

        $this->evaluar()->assertOk();

        $this->assertSame(1, Notificacion::count());
        $this->assertEquals($primera->updated_at, Notificacion::firstOrFail()->updated_at);
    }

    /**
     * Si el instructor no leyó el aviso, una reevaluación lo actualiza en vez de crear otro.
     */
    public function test_reevaluar_actualiza_el_aviso_no_leido(): void
    {
        $this->evaluar()->assertOk();
        $this->sha = str_repeat('b', 40);
        $this->evaluar()->assertOk();

        $this->assertSame(1, Notificacion::count());
        $this->assertSame(
            'Ana Pérez volvió a evaluar el mini proyecto «Calculadora» (básico, semana 1 de «Fundamentos JS») '
            . 'y obtuvo 74/100 (intento 2).',
            Notificacion::firstOrFail()->mensaje
        );
    }

    /**
     * Si el instructor ya leyó el aviso, una reevaluación crea uno nuevo.
     */
    public function test_reevaluar_despues_de_leer_crea_otro_aviso(): void
    {
        $this->evaluar()->assertOk();

        Sanctum::actingAs($this->instructor);
        $this->patchJson('/api/notificaciones/' . Notificacion::firstOrFail()->id . '/leida')->assertOk();

        $this->sha = str_repeat('b', 40);
        $this->evaluar()->assertOk();

        Sanctum::actingAs($this->instructor);

        $this->getJson('/api/notificaciones')
            ->assertOk()
            ->assertJsonPath('no_leidas', 1)
            ->assertJsonCount(2, 'notificaciones.data')
            ->assertJsonPath('notificaciones.data.0.datos.intento', 2);
    }

    /**
     * En Modo Personal nadie recibe aviso: el instructor nunca ve esas guías.
     */
    public function test_modo_personal_no_avisa(): void
    {
        $guia = Guia::create(['nombre' => 'Mía', 'origen' => 'personal', 'usuario_id' => $this->estudiante->id]);
        $proyecto = $this->crearProyecto($guia);
        $this->guardarRepo($this->estudiante, $proyecto);

        Sanctum::actingAs($this->estudiante);
        $this->postJson("/api/mini-proyectos/{$proyecto->id}/entrega/evaluar")->assertOk();

        $this->assertSame(0, Notificacion::count());
    }

    /**
     * Otro instructor no ve ni marca el aviso, y el estudiante no tiene campanita.
     */
    public function test_cada_instructor_solo_ve_y_marca_lo_suyo(): void
    {
        $this->evaluar()->assertOk();
        $aviso = Notificacion::firstOrFail();

        Sanctum::actingAs(User::factory()->create(['rol' => 'instructor']));
        $this->getJson('/api/notificaciones')->assertOk()->assertJsonPath('no_leidas', 0)
            ->assertJsonCount(0, 'notificaciones.data');
        $this->patchJson("/api/notificaciones/{$aviso->id}/leida")->assertForbidden();

        // El estudiante también usa la campanita, pero nunca ve ni marca los avisos del instructor
        Sanctum::actingAs($this->estudiante);
        $this->getJson('/api/notificaciones')->assertOk()->assertJsonCount(0, 'notificaciones.data');
        $this->patchJson("/api/notificaciones/{$aviso->id}/leida")->assertForbidden();

        $this->assertNull($aviso->fresh()->leida_en);
    }

    /**
     * Liberar una semana avisa al estudiante, que ve el aviso, lo marca como leído
     * y no puede tocar los de otro estudiante.
     */
    public function test_el_estudiante_ve_y_marca_sus_avisos(): void
    {
        $guia = Guia::firstOrFail();
        $this->crearSemana($guia, 2);

        $this->liberar(2)->assertOk();

        Sanctum::actingAs($this->estudiante);
        $respuesta = $this->getJson('/api/notificaciones')
            ->assertOk()
            ->assertJsonPath('no_leidas', 1)
            ->assertJsonPath('notificaciones.data.0.tipo', 'semana_liberada')
            ->assertJsonPath('notificaciones.data.0.mensaje', 'Ya puedes ver la semana 2 de «Fundamentos JS» en «ADSO».')
            ->assertJsonPath('notificaciones.data.0.datos.guia_id', $guia->id)
            ->assertJsonPath('notificaciones.data.0.datos.guia', 'Fundamentos JS')
            ->assertJsonPath('notificaciones.data.0.datos.grupo', 'ADSO')
            ->assertJsonPath('notificaciones.data.0.datos.numero_semana', 2);

        $avisoId = $respuesta->json('notificaciones.data.0.id');

        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));
        $this->patchJson("/api/notificaciones/{$avisoId}/leida")->assertForbidden();

        Sanctum::actingAs($this->estudiante);
        $this->patchJson("/api/notificaciones/{$avisoId}/leida")->assertOk();
        $this->getJson('/api/notificaciones')->assertJsonPath('no_leidas', 0);
    }

    /**
     * Se crea un aviso por cada guía del grupo que tiene la semana liberada,
     * y nadie de otro grupo recibe nada.
     */
    public function test_liberar_semana_avisa_por_cada_guia_que_la_tiene(): void
    {
        $grupo = Grupo::firstOrFail();
        $this->crearSemana(Guia::firstOrFail(), 2);
        $this->crearSemana(Guia::create(['nombre' => 'Bases de datos', 'origen' => 'grupo', 'grupo_id' => $grupo->id]), 2);
        Guia::create(['nombre' => 'Corta', 'origen' => 'grupo', 'grupo_id' => $grupo->id]); // sin semana 2

        $otroGrupo = Grupo::create(['nombre' => 'Otro', 'instructor_id' => $this->instructor->id, 'codigo_acceso' => 'XYZ789']);
        $ajeno = User::factory()->create(['rol' => 'estudiante']);
        $otroGrupo->estudiantes()->attach($ajeno->id);

        $this->liberar(2)->assertOk();

        $guias = Notificacion::where('usuario_id', $this->estudiante->id)->where('tipo', 'semana_liberada')
            ->get()->pluck('datos.guia')->sort()->values()->all();
        $this->assertSame(['Bases de datos', 'Fundamentos JS'], $guias);
        $this->assertSame(0, Notificacion::where('usuario_id', $ajeno->id)->count());
        $this->assertSame(0, Notificacion::where('usuario_id', $this->instructor->id)->count());
    }

    /**
     * Bajar la semana o dejarla igual no avisa; saltar varias dice "hasta la semana N".
     */
    public function test_solo_avisa_si_la_semana_sube(): void
    {
        $guia = Guia::firstOrFail();
        $this->crearSemana($guia, 3);

        $this->liberar(1)->assertOk();
        $this->assertSame(0, Notificacion::count());

        $this->liberar(3)->assertOk();
        $this->assertSame(
            'Ya puedes ver hasta la semana 3 de «Fundamentos JS» en «ADSO».',
            Notificacion::where('tipo', 'semana_liberada')->value('mensaje')
        );

        $this->liberar(2)->assertOk();
        $this->assertSame(1, Notificacion::count());
    }

    /**
     * Una guía de grupo nueva avisa a los estudiantes; repetir el mismo PDF no avisa otra vez.
     */
    public function test_guia_nueva_de_grupo_avisa_a_los_estudiantes(): void
    {
        $grupo = Grupo::firstOrFail();
        $guia = Guia::create(['nombre' => 'Laravel', 'origen' => 'grupo', 'grupo_id' => $grupo->id]);

        $this->fingirSubida($guia, true);
        Sanctum::actingAs($this->instructor);
        $this->subirGuia(['grupo_id' => $grupo->id])->assertCreated();

        $aviso = Notificacion::where('usuario_id', $this->estudiante->id)->where('tipo', 'guia_nueva')->firstOrFail();
        $this->assertSame('Tu instructor subió la guía «Laravel» al grupo «ADSO».', $aviso->mensaje);
        $this->assertSame(['grupo_id' => $grupo->id, 'grupo' => 'ADSO', 'guia_id' => $guia->id, 'guia' => 'Laravel', 'numero_semana' => 1], $aviso->datos);

        $this->fingirSubida($guia, false);
        $this->subirGuia(['grupo_id' => $grupo->id])->assertOk();
        $this->assertSame(1, Notificacion::where('tipo', 'guia_nueva')->count());
    }

    /**
     * Una guía personal no avisa a nadie.
     */
    public function test_guia_personal_no_avisa(): void
    {
        $guia = Guia::create(['nombre' => 'Mía', 'origen' => 'personal', 'usuario_id' => $this->estudiante->id]);

        $this->fingirSubida($guia, true);
        Sanctum::actingAs($this->estudiante);
        $this->subirGuia()->assertCreated();

        $this->assertSame(0, Notificacion::count());
    }

    /**
     * Si crear el aviso falla, la semana queda liberada igual.
     */
    public function test_si_el_aviso_de_semana_falla_la_semana_se_libera(): void
    {
        $this->mock(NotificacionService::class, fn ($mock) => $mock
            ->shouldReceive('avisarSemanaLiberada')->andThrow(new \RuntimeException('Base caída')));

        $this->liberar(2)->assertOk()->assertJsonPath('semana_actual', 2);
        $this->assertSame(2, Grupo::firstOrFail()->semana_actual);
    }

    /**
     * El instructor cambia la semana actual del grupo.
     */
    private function liberar(int $semana)
    {
        Sanctum::actingAs($this->instructor);

        return $this->patchJson('/api/grupos/' . Grupo::firstOrFail()->id . '/semana-actual', ['semana_actual' => $semana]);
    }

    /**
     * Crea una semana lista en la guía indicada.
     */
    private function crearSemana(Guia $guia, int $numero): void
    {
        $guia->semanas()->create([
            'numero_semana'     => $numero,
            'titulo'            => "Semana {$numero}",
            'resumen'           => 'Resumen.',
            'contenido_texto'   => "Texto {$numero}",
            'hash_contenido'    => hash('sha256', "Texto {$numero}"),
            'estado_generacion' => 'listo',
        ]);
    }

    /**
     * Simula el procesamiento del PDF: devuelve la guía indicada como nueva o repetida.
     */
    private function fingirSubida(Guia $guia, bool $creada): void
    {
        $this->mock(ProcesarGuiaService::class, fn ($mock) => $mock
            ->shouldReceive('crear')->andReturn(['guia' => $guia, 'creada' => $creada]));
    }

    /**
     * Sube una guía con un PDF de prueba.
     */
    private function subirGuia(array $datos = [])
    {
        return $this->post('/api/guias', $datos + [
            'nombre'  => 'Guía de prueba',
            'archivo' => UploadedFile::fake()->createWithContent('guia.pdf', '%PDF-1.4 contenido de prueba'),
        ], ['Accept' => 'application/json']);
    }

    /**
     * Marcar todas deja la campanita en cero.
     */
    public function test_marcar_todas_como_leidas(): void
    {
        $this->evaluar()->assertOk();

        Sanctum::actingAs($this->instructor);

        $this->patchJson('/api/notificaciones/leidas')->assertOk()->assertJsonPath('marcadas', 1);
        $this->getJson('/api/notificaciones')->assertOk()->assertJsonPath('no_leidas', 0);
    }

    /**
     * Si el aviso falla, el estudiante igual recibe su evaluación.
     */
    public function test_si_el_aviso_falla_la_evaluacion_se_guarda(): void
    {
        $this->mock(NotificacionService::class)
            ->shouldReceive('avisarEvaluacion')
            ->andThrow(new \RuntimeException('Base caída'));

        $this->evaluar()
            ->assertOk()
            ->assertJsonPath('entrega.estado', 'evaluado')
            ->assertJsonPath('entrega.puntaje', 74);
    }

    /**
     * Pide evaluar la entrega del estudiante del grupo.
     */
    private function evaluar()
    {
        Sanctum::actingAs($this->estudiante);

        return $this->postJson("/api/mini-proyectos/{$this->proyecto->id}/entrega/evaluar");
    }

    /**
     * Crea en la guía una semana 1 lista con el mini proyecto de la calculadora.
     */
    private function crearProyecto(Guia $guia): MiniProyecto
    {
        $semana = $guia->semanas()->create([
            'numero_semana'     => 1,
            'titulo'            => 'Semana 1',
            'resumen'           => 'Resumen.',
            'contenido_texto'   => 'Texto',
            'hash_contenido'    => hash('sha256', 'Texto'),
            'estado_generacion' => 'listo',
        ]);

        return $semana->miniProyectos()->create([
            'guia_id'                => $guia->id,
            'orden'                  => 1,
            'dificultad'             => 'basico',
            'titulo'                 => 'Calculadora',
            'descripcion'            => 'Descripción',
            'requisitos_funcionales' => ['Suma dos números'],
            'buenas_practicas'       => ['Usa nombres descriptivos'],
            'criterios_evaluacion'   => [
                ['criterio' => 'Las operaciones funcionan', 'peso' => 60],
                ['criterio' => 'División entre cero', 'peso' => 20],
                ['criterio' => 'Claridad', 'peso' => 20],
            ],
        ]);
    }

    /**
     * Guarda el link del repositorio del estudiante en el mini proyecto.
     */
    private function guardarRepo(User $estudiante, MiniProyecto $proyecto): void
    {
        $proyecto->entregas()->create(['estudiante_id' => $estudiante->id, 'repo_url' => self::REPO]);
    }

    /**
     * Simula GitHub, con un solo archivo y el commit de $this->sha, y una evaluación de Gemini que da 74.
     */
    private function fingirServicios(): void
    {
        Http::fake([
            'api.github.com/*' => function (Request $peticion) {
                $url = $peticion->url();

                if (str_contains($url, '/git/blobs/')) {
                    return Http::response('function sumar(a, b) { return a + b }');
                }

                if (str_contains($url, '/git/trees/')) {
                    return Http::response([
                        'tree'      => [['path' => 'index.js', 'type' => 'blob', 'sha' => 'blob0', 'size' => 40]],
                        'truncated' => false,
                    ]);
                }

                if (str_contains($url, '/commits/')) {
                    return Http::response(['sha' => $this->sha, 'commit' => ['tree' => ['sha' => 'arbol1']]]);
                }

                return Http::response(['private' => false, 'default_branch' => 'main']);
            },
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($this->evaluacion())]]]]],
            ]),
        ]);
    }

    /**
     * Evaluación válida según el contrato 2.3. Con estos pesos da 74.
     */
    private function evaluacion(): array
    {
        return [
            'resumen'   => 'Cumple lo principal.',
            'criterios' => [
                ['criterio' => 'Las operaciones funcionan', 'cumplimiento' => 100, 'comentario' => 'Correctas.'],
                ['criterio' => 'División entre cero', 'cumplimiento' => 0, 'comentario' => 'No se valida.'],
                ['criterio' => 'Claridad', 'cumplimiento' => 70, 'comentario' => 'Hay repetición.'],
            ],
            'requisitos' => [
                ['requisito' => 'Suma dos números', 'cumple' => true, 'comentario' => 'En index.js.'],
            ],
            'buenas_practicas' => [
                ['practica' => 'Usa nombres descriptivos', 'cumple' => true, 'comentario' => 'Son claros.'],
            ],
            'sugerencias' => ['Valida el divisor.'],
        ];
    }
}
