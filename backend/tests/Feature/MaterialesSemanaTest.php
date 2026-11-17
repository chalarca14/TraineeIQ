<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Models\GuiaSemanaMaterial;
use App\Models\Notificacion;
use App\Models\User;
use App\Services\PdfTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Guías de la semana: el instructor adjunta guías detalladas a una semana de la guía principal,
 * y la IA las usa (junto con la planeación) para las recomendaciones y los mini proyectos.
 */
class MaterialesSemanaTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $estudiante;

    private Grupo $grupo;

    private Guia $guia;

    /** @var list<string> Texto que "leerá" el PDF en cada subida, en orden */
    private array $textos = [];

    /**
     * Grupo en la semana 1 con una guía de dos semanas. El lector de PDF se simula.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['rol' => 'instructor']);
        $this->estudiante = User::factory()->create(['rol' => 'estudiante']);
        $this->grupo = Grupo::create(['nombre' => 'ADSO', 'instructor_id' => $this->instructor->id, 'codigo_acceso' => 'ABC123']);
        $this->grupo->estudiantes()->attach($this->estudiante->id);

        $this->guia = Guia::create(['nombre' => 'Planeación', 'origen' => 'grupo', 'grupo_id' => $this->grupo->id, 'semanas_totales' => 2]);
        foreach ([1, 2] as $numero) {
            $this->crearSemana($this->guia, $numero);
        }

        $this->mock(PdfTextExtractor::class, fn ($mock) => $mock->shouldReceive('extraer')
            ->andReturnUsing(fn () => array_shift($this->textos) ?? $this->texto('Guía detallada')));
    }

    /**
     * El instructor adjunta varias guías a una semana; se listan en orden y viajan en el detalle.
     */
    public function test_el_instructor_adjunta_varias_guias_a_una_semana(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->subir(1, 'Taller de variables', 'a')
            ->assertCreated()
            ->assertJsonPath('material.nombre', 'Taller de variables')
            ->assertJsonPath('semana_lista', false)
            ->assertJsonMissingPath('material.hash_archivo');
        $this->subir(1, 'Guía de funciones', 'b')->assertCreated();

        $this->getJson($this->url(1))
            ->assertOk()
            ->assertJsonCount(2, 'materiales')
            ->assertJsonPath('materiales.0.nombre', 'Taller de variables')
            ->assertJsonPath('materiales.1.nombre', 'Guía de funciones');

        $this->getJson("/api/guias/{$this->guia->id}/semanas/1")->assertOk()->assertJsonCount(2, 'semana.materiales');
    }

    /**
     * Solo el instructor del grupo adjunta: ni el estudiante ni otro instructor.
     */
    public function test_solo_el_instructor_del_grupo_adjunta(): void
    {
        Sanctum::actingAs($this->estudiante);
        $this->subir(1, 'Mía', 'a')->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['rol' => 'instructor']));
        $this->subir(1, 'Ajena', 'b')->assertForbidden();

        $this->assertSame(0, GuiaSemanaMaterial::count());
    }

    /**
     * En una guía del Modo Personal no se adjuntan guías de la semana (por ahora).
     */
    public function test_no_se_adjunta_a_una_guia_personal(): void
    {
        $personal = Guia::create(['nombre' => 'Mía', 'origen' => 'personal', 'usuario_id' => $this->estudiante->id]);
        $this->crearSemana($personal, 1);
        Sanctum::actingAs($this->estudiante);

        $this->post("/api/guias/{$personal->id}/semanas/1/materiales", [
            'nombre'  => 'Detalle',
            'archivo' => UploadedFile::fake()->createWithContent('g.pdf', '%PDF-1.4 x'),
        ], ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo se pueden adjuntar guías de la semana a las guías de un grupo.');
    }

    /**
     * Archivo repetido, sin texto, más de 5 guías o demasiado texto: 422 con mensaje claro.
     */
    public function test_rechaza_repetidos_sin_texto_tope_y_demasiado_largo(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->subir(1, 'Taller', 'a')->assertCreated();
        $this->subir(1, 'Taller otra vez', 'a')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Ese archivo ya está adjunto a esta semana.');

        $this->textos = ['corto'];
        $this->subir(1, 'Escaneado', 'z')->assertUnprocessable();

        $this->textos = [str_repeat('Texto muy largo de la guía. ', 3000)];
        $this->subir(1, 'Enorme', 'y')->assertUnprocessable();

        foreach (['b', 'c', 'd', 'e'] as $contenido) {
            $this->subir(1, "Guía {$contenido}", $contenido)->assertCreated();
        }
        $this->subir(1, 'La sexta', 'f')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Esta semana ya tiene 5 guías. Quita una para agregar otra.');
    }

    /**
     * Las guías de la semana viajan en el prompt de Gemini al preparar la semana.
     */
    public function test_la_ia_recibe_las_guias_de_la_semana(): void
    {
        Sanctum::actingAs($this->instructor);
        $this->textos = [$this->texto('Ejercicio detallado de variables con let y const')];
        $this->subir(1, 'Taller de variables', 'a')->assertCreated();

        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($this->contenido())]]]]]])]);
        $this->postJson("/api/guias/{$this->guia->id}/semanas/1/generar")->assertOk();

        Http::assertSent(function ($peticion) {
            $prompt = $peticion['contents'][0]['parts'][0]['text'];

            return str_contains($prompt, '<guia_semana>')
                && str_contains($prompt, 'Nombre: Taller de variables')
                && str_contains($prompt, 'Ejercicio detallado de variables');
        });
    }

    /**
     * El estudiante ve las guías solo de semanas liberadas.
     */
    public function test_el_estudiante_las_ve_solo_si_la_semana_esta_liberada(): void
    {
        Sanctum::actingAs($this->instructor);
        $this->subir(1, 'Semana uno', 'a')->assertCreated();
        $this->subir(2, 'Semana dos', 'b')->assertCreated();

        Sanctum::actingAs($this->estudiante);
        $this->getJson($this->url(1))->assertOk()->assertJsonCount(1, 'materiales');
        $this->getJson($this->url(2))->assertForbidden();
    }

    /**
     * Solo se avisa a los estudiantes si la semana ya está liberada.
     */
    public function test_avisa_solo_en_semanas_liberadas(): void
    {
        Sanctum::actingAs($this->instructor);

        $this->subir(2, 'Futura', 'a')->assertCreated();
        $this->assertSame(0, Notificacion::count());

        $this->subir(1, 'Actual', 'b')->assertCreated();
        $this->assertSame(1, Notificacion::where('usuario_id', $this->estudiante->id)->where('tipo', 'material_nuevo')->count());
    }

    /**
     * El instructor quita una guía; una que no es de esa semana responde 404.
     */
    public function test_el_instructor_quita_una_guia(): void
    {
        Sanctum::actingAs($this->instructor);
        $id = $this->subir(1, 'Taller', 'a')->json('material.id');

        $this->deleteJson($this->url(2) . "/{$id}")->assertNotFound();
        $this->deleteJson($this->url(1) . "/{$id}")->assertOk();

        $this->assertSame(0, GuiaSemanaMaterial::count());
    }

    /**
     * Sube una guía de la semana. El contenido del archivo distingue un archivo de otro.
     */
    private function subir(int $numero, string $nombre, string $contenido)
    {
        return $this->post($this->url($numero), [
            'nombre'  => $nombre,
            'archivo' => UploadedFile::fake()->createWithContent("{$contenido}.pdf", "%PDF-1.4 {$contenido}"),
        ], ['Accept' => 'application/json']);
    }

    /**
     * Ruta de las guías de una semana de la guía principal.
     */
    private function url(int $numero): string
    {
        return "/api/guias/{$this->guia->id}/semanas/{$numero}/materiales";
    }

    /**
     * Un texto con más de 100 caracteres, como el de una guía real.
     */
    private function texto(string $inicio): string
    {
        return $inicio . '. ' . str_repeat('Contenido de práctica para la semana. ', 5);
    }

    /**
     * Crea una semana pendiente.
     */
    private function crearSemana(Guia $guia, int $numero): GuiaSemana
    {
        return $guia->semanas()->create([
            'numero_semana'     => $numero,
            'titulo'            => "Semana {$numero}",
            'resumen'           => 'Resumen.',
            'contenido_texto'   => "Texto de la planeación de la semana {$numero}.",
            'hash_contenido'    => hash('sha256', "Texto {$numero}"),
            'estado_generacion' => 'pendiente',
        ]);
    }

    /**
     * Contenido válido de una semana: 4 recomendaciones y 3 mini proyectos.
     */
    private function contenido(): array
    {
        $niveles = ['basico', 'recomendado', 'importante', 'avanzado'];
        $dificultades = ['basico', 'intermedio', 'avanzado'];

        return [
            'recomendaciones' => array_map(fn ($i) => [
                'orden' => $i + 1, 'nivel' => $niveles[$i], 'titulo' => "Repasa el tema {$i}",
                'tema' => "Tema {$i}", 'tema_semana' => 'Variables', 'por_que' => 'Te recomiendo practicar.',
                'descripcion' => 'Haz ejercicios cortos.',
            ], range(0, 3)),
            'mini_proyectos' => array_map(fn ($i) => [
                'orden' => $i + 1, 'dificultad' => $dificultades[$i], 'titulo' => "Proyecto {$i}",
                'descripcion' => 'Un programa.', 'contenido' => '1. Paso uno.',
                'requisitos_funcionales' => ['Uno', 'Dos'], 'buenas_practicas' => ['Nombres claros'],
                'criterios_evaluacion' => [['criterio' => 'Funciona', 'peso' => 60], ['criterio' => 'Claro', 'peso' => 40]],
            ], range(0, 2)),
        ];
    }
}
