<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Recorre los dos flujos del plan usando solo la API, como lo haría el frontend.
 * Gemini y GitHub son simulados: lo real se comprueba en el despliegue.
 */
class FlujosCompletosTest extends TestCase
{
    use RefreshDatabase;

    private const TEXTO = "Actividad 1: reconocer los tipos de datos en JavaScript.\n\n"
        . 'Actividad 2: construir funciones reutilizables con parámetros.';

    private const REPO = 'https://github.com/ejemplo/calculadora';

    /**
     * Prepara el disco falso, el texto del PDF y las respuestas de los servicios externos.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->mock(PdfTextExtractor::class, fn ($mock) => $mock->shouldReceive('extraer')->andReturn(self::TEXTO));
        $this->fingirServicios();
    }

    /**
     * Instructor y estudiantes: grupo, guía, semanas, liberación, entrega y seguimiento.
     */
    public function test_flujo_completo_de_grupo(): void
    {
        $instructor = User::factory()->create(['rol' => 'instructor']);
        $ana = User::factory()->create(['rol' => 'estudiante']);
        $luis = User::factory()->create(['rol' => 'estudiante']);
        $ajeno = User::factory()->create(['rol' => 'estudiante']);

        // El instructor crea el grupo y los estudiantes entran con el código
        Sanctum::actingAs($instructor);
        $grupo = $this->postJson('/api/grupos', ['nombre' => 'ADSO'])->assertCreated()->json('data');

        foreach ([$ana, $luis] as $estudiante) {
            Sanctum::actingAs($estudiante);
            $this->postJson('/api/grupos/unirse', ['codigo_acceso' => $grupo['codigo_acceso']])->assertOk();
        }

        // Subir la guía deja las semanas pendientes y el frontend las genera una por una
        Sanctum::actingAs($instructor);
        $guiaId = $this->subirGuia(['grupo_id' => $grupo['id']]);
        $this->generarSemanas($guiaId);

        // Antes de liberar, el estudiante solo llega a la semana actual
        Sanctum::actingAs($ana);
        $this->getJson("/api/guias/{$guiaId}/semanas/1")->assertOk();
        $this->getJson("/api/guias/{$guiaId}/semanas/2")->assertForbidden();

        Sanctum::actingAs($instructor);
        $this->patchJson("/api/grupos/{$grupo['id']}/semana-actual", ['semana_actual' => 2])->assertOk();

        // Dos estudiantes del mismo grupo reciben exactamente el mismo contenido
        Sanctum::actingAs($ana);
        $contenidoAna = $this->getJson("/api/guias/{$guiaId}/semanas/2")->assertOk()->json('semana');

        Sanctum::actingAs($luis);
        $contenidoLuis = $this->getJson("/api/guias/{$guiaId}/semanas/2")->assertOk()->json('semana');

        $this->assertSame($contenidoAna, $contenidoLuis);
        $this->assertCount(3, $contenidoAna['mini_proyectos']);

        // Quien no es del grupo no entra
        Sanctum::actingAs($ajeno);
        $this->getJson("/api/guias/{$guiaId}/semanas/1")->assertForbidden();

        // Ana entrega y evalúa; su instructor ve el resultado
        Sanctum::actingAs($ana);
        $proyectoId = $contenidoAna['mini_proyectos'][0]['id'];
        $this->entregarYEvaluar($proyectoId);

        Sanctum::actingAs($instructor);
        $this->getJson("/api/grupos/{$grupo['id']}/entregas")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.estudiante.id', $ana->id)
            ->assertJsonPath('data.0.repo_url', self::REPO)
            ->assertJsonPath('data.0.puntaje', 80);
    }

    /**
     * Estudiante solo: sube su guía, ve todas las semanas y nadie más puede verla.
     */
    public function test_flujo_completo_personal(): void
    {
        $dueno = User::factory()->create(['rol' => 'estudiante']);

        Sanctum::actingAs($dueno);
        $guiaId = $this->subirGuia();
        $this->generarSemanas($guiaId);

        // En Modo Personal no hay que esperar a que liberen las semanas
        $this->getJson("/api/guias/{$guiaId}/semanas")
            ->assertOk()
            ->assertJsonPath('semanas.0.disponible', true)
            ->assertJsonPath('semanas.1.disponible', true);

        $proyectos = $this->getJson("/api/guias/{$guiaId}/semanas/2")->assertOk()->json('semana.mini_proyectos');
        $this->entregarYEvaluar($proyectos[0]['id']);

        // Ni otro estudiante ni un instructor pueden ver la guía o el mini proyecto
        foreach (['estudiante', 'instructor'] as $rol) {
            Sanctum::actingAs(User::factory()->create(['rol' => $rol]));

            $this->getJson("/api/guias/{$guiaId}/semanas/1")->assertForbidden();
            $this->getJson("/api/mini-proyectos/{$proyectos[0]['id']}")->assertForbidden();
        }
    }

    /**
     * Sube la guía con el usuario actual y devuelve su id.
     */
    private function subirGuia(array $datos = []): int
    {
        return $this->post('/api/guias', $datos + [
            'nombre'  => 'Guía de JavaScript',
            'archivo' => UploadedFile::fake()->createWithContent('guia.pdf', '%PDF-1.4 contenido de prueba'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonCount(2, 'guia.semanas')
            ->assertJsonPath('guia.semanas.0.estado_generacion', 'pendiente')
            ->json('guia.id');
    }

    /**
     * Genera las dos semanas, como hace la cola del frontend.
     */
    private function generarSemanas(int $guiaId): void
    {
        foreach ([1, 2] as $numero) {
            $this->postJson("/api/guias/{$guiaId}/semanas/{$numero}/generar")
                ->assertOk()
                ->assertJsonPath('semana.estado_generacion', 'listo');
        }
    }

    /**
     * Guarda el repositorio y pide la evaluación con el usuario actual.
     */
    private function entregarYEvaluar(int $proyectoId): void
    {
        $this->putJson("/api/mini-proyectos/{$proyectoId}/entrega", ['repo_url' => self::REPO])->assertCreated();

        $this->postJson("/api/mini-proyectos/{$proyectoId}/entrega/evaluar")
            ->assertOk()
            ->assertJsonPath('entrega.estado', 'evaluado')
            ->assertJsonPath('entrega.puntaje', 80)
            ->assertJsonPath('intentos_restantes', 2);
    }

    /**
     * Simula GitHub y responde a cada llamada de Gemini según lo que pide su esquema.
     */
    private function fingirServicios(): void
    {
        Http::fake([
            'api.github.com/*' => function (Request $peticion) {
                $url = $peticion->url();

                return match (true) {
                    str_contains($url, '/git/blobs/') => Http::response('function sumar(a, b) { return a + b }'),
                    str_contains($url, '/git/trees/') => Http::response(['truncated' => false, 'tree' => [
                        ['path' => 'index.js', 'type' => 'blob', 'sha' => 'blob1', 'size' => 40],
                    ]]),
                    str_contains($url, '/commits/') => Http::response([
                        'sha'    => str_repeat('a', 40),
                        'commit' => ['tree' => ['sha' => 'arbol1']],
                    ]),
                    default => Http::response(['private' => false, 'default_branch' => 'main']),
                };
            },
            'generativelanguage.googleapis.com/*' => function (Request $peticion) {
                $esquema = $peticion['generationConfig']['responseJsonSchema']['properties'];

                $datos = match (true) {
                    isset($esquema['semanas'])         => $this->semanas(),
                    isset($esquema['recomendaciones']) => $this->contenido(),
                    default                            => $this->evaluacion(),
                };

                return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($datos)]]]]]]);
            },
        ]);
    }

    /**
     * División del PDF en dos semanas.
     */
    private function semanas(): array
    {
        $semana = fn (int $numero, string $inicio) => [
            'numero_semana' => $numero,
            'titulo'        => "Semana {$numero}",
            'resumen'       => "Resumen de la semana {$numero}.",
            'inicio_texto'  => $inicio,
            'temas'         => [['nombre_tema' => "Tema {$numero}", 'descripcion' => 'Descripción del tema.']],
        ];

        return ['semanas' => [
            $semana(1, 'Actividad 1: reconocer los tipos de datos'),
            $semana(2, 'Actividad 2: construir funciones'),
        ]];
    }

    /**
     * Contenido válido de una semana: 4 recomendaciones y 3 mini proyectos.
     */
    private function contenido(): array
    {
        $niveles = ['basico', 'recomendado', 'importante', 'avanzado'];
        $dificultades = ['basico', 'intermedio', 'avanzado'];

        return [
            'recomendaciones' => array_map(fn ($nivel, $indice) => [
                'orden'       => $indice + 1,
                'nivel'       => $nivel,
                'titulo'      => 'Recomendación ' . ($indice + 1),
                'descripcion' => 'Descripción de la recomendación.',
            ], $niveles, array_keys($niveles)),
            'mini_proyectos' => array_map(fn ($dificultad, $indice) => [
                'orden'                  => $indice + 1,
                'dificultad'             => $dificultad,
                'titulo'                 => "Proyecto {$dificultad}",
                'descripcion'            => 'Resumen del proyecto.',
                'contenido'              => "1. Primer paso.\n2. Segundo paso.",
                'requisitos_funcionales' => ['Cumple la función principal'],
                'buenas_practicas'       => ['Usa nombres descriptivos'],
                'criterios_evaluacion'   => [
                    ['criterio' => 'Funciona', 'peso' => 60],
                    ['criterio' => 'Claridad', 'peso' => 40],
                ],
            ], $dificultades, array_keys($dificultades)),
        ];
    }

    /**
     * Evaluación válida. Con los pesos 60 y 40 da 80.
     */
    private function evaluacion(): array
    {
        return [
            'resumen'   => 'Cumple lo principal.',
            'criterios' => [
                ['criterio' => 'Funciona', 'cumplimiento' => 100, 'comentario' => 'Correcto.'],
                ['criterio' => 'Claridad', 'cumplimiento' => 50, 'comentario' => 'Se puede mejorar.'],
            ],
            'requisitos' => [
                ['requisito' => 'Cumple la función principal', 'cumple' => true, 'comentario' => 'En index.js.'],
            ],
            'buenas_practicas' => [
                ['practica' => 'Usa nombres descriptivos', 'cumple' => true, 'comentario' => 'Son claros.'],
            ],
            'sugerencias' => ['Agrega comentarios.'],
        ];
    }
}
