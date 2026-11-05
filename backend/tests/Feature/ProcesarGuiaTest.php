<?php

namespace Tests\Feature;

use App\Models\EntregaMiniProyecto;
use App\Models\Grupo;
use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Models\User;
use App\Services\PdfTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProcesarGuiaTest extends TestCase
{
    use RefreshDatabase;

    private const TEXTO = "Portada de la guía de aprendizaje del SENA.\n\n"
        . "Actividad 1: reconocer los tipos de datos en JavaScript y practicar con variables.\n\n"
        . "Actividad 2: construir funciones reutilizables con parámetros y retorno de valores.";

    /**
     * Todas las pruebas usan un disco falso para no escribir archivos reales.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Subir una guía crea sus semanas en pendiente, con el texto real del PDF.
     */
    public function test_subir_una_guia_crea_semanas_pendientes(): void
    {
        $this->fingirPdf(self::TEXTO);
        Http::fake(['*' => $this->respuestaGemini($this->dosSemanas())]);
        $estudiante = $this->actuarComoEstudiante();

        $respuesta = $this->subir(['nombre' => 'Guía de JS'])
            ->assertCreated()
            ->assertJsonPath('guia.origen', 'personal')
            ->assertJsonPath('guia.usuario_id', $estudiante->id)
            ->assertJsonPath('guia.grupo_id', null)
            ->assertJsonPath('guia.semanas_totales', 2)
            ->assertJsonCount(2, 'guia.semanas')
            ->assertJsonCount(3, 'guia.temas')
            ->assertJsonPath('guia.semanas.0.estado_generacion', 'pendiente');

        $this->assertArrayNotHasKey('contenido_texto', $respuesta->json('guia.semanas.0'));
        Storage::disk('public')->assertExists(Guia::first()->archivo_pdf);

        [$semana1, $semana2] = GuiaSemana::orderBy('numero_semana')->get()->all();

        $this->assertStringStartsWith('Portada', $semana1->contenido_texto);
        $this->assertStringEndsWith('variables.', $semana1->contenido_texto);
        $this->assertStringStartsWith('Actividad 2', $semana2->contenido_texto);
        $this->assertSame(hash('sha256', $semana2->contenido_texto), $semana2->hash_contenido);
        $this->assertSame([3], $semana2->temas->pluck('orden')->all());
    }

    /**
     * La frase de inicio se encuentra aunque cambien los espacios y las mayúsculas.
     */
    public function test_la_frase_de_inicio_tolera_espacios_y_mayusculas(): void
    {
        $this->fingirPdf(str_replace('Actividad 2: construir', "ACTIVIDAD   2:\nconstruir", self::TEXTO));
        Http::fake(['*' => $this->respuestaGemini($this->dosSemanas())]);
        $this->actuarComoEstudiante();

        $this->subir()->assertCreated();

        $this->assertStringStartsWith('ACTIVIDAD', GuiaSemana::where('numero_semana', 2)->first()->contenido_texto);
    }

    /**
     * El instructor sube la guía a su grupo y queda sin usuario_id.
     */
    public function test_instructor_sube_una_guia_a_su_grupo(): void
    {
        $this->fingirPdf(self::TEXTO);
        Http::fake(['*' => $this->respuestaGemini($this->dosSemanas())]);

        $instructor = User::factory()->create(['rol' => 'instructor']);
        $grupo = Grupo::create(['nombre' => 'ADSO', 'instructor_id' => $instructor->id, 'codigo_acceso' => 'ABC123']);
        Sanctum::actingAs($instructor);

        $this->subir(['grupo_id' => $grupo->id])
            ->assertCreated()
            ->assertJsonPath('guia.origen', 'grupo')
            ->assertJsonPath('guia.grupo_id', $grupo->id)
            ->assertJsonPath('guia.usuario_id', null);
    }

    /**
     * El instructor debe indicar el grupo, y el estudiante no puede enviarlo.
     */
    public function test_valida_el_grupo_segun_el_rol(): void
    {
        Http::fake();

        Sanctum::actingAs(User::factory()->create(['rol' => 'instructor']));
        $this->subir()->assertUnprocessable()->assertJsonValidationErrors('grupo_id');

        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));
        $this->subir(['grupo_id' => 1])->assertUnprocessable()->assertJsonValidationErrors('grupo_id');

        Http::assertNothingSent();
    }

    /**
     * Enviar usuario_id no permite crear una guía a nombre de otra persona.
     */
    public function test_no_se_puede_suplantar_al_dueno(): void
    {
        $this->fingirPdf(self::TEXTO);
        Http::fake(['*' => $this->respuestaGemini($this->dosSemanas())]);

        $otro = User::factory()->create(['rol' => 'estudiante']);
        $estudiante = $this->actuarComoEstudiante();

        $this->subir(['usuario_id' => $otro->id])
            ->assertCreated()
            ->assertJsonPath('guia.usuario_id', $estudiante->id);
    }

    /**
     * Un JSON mal formado se reintenta una vez y luego se guarda normal.
     */
    public function test_reintenta_una_vez_si_el_json_es_invalido(): void
    {
        $this->fingirPdf(self::TEXTO);
        Http::fakeSequence()
            ->push($this->cuerpoGemini('esto no es JSON'))
            ->push($this->cuerpoGemini(json_encode($this->dosSemanas())));
        $this->actuarComoEstudiante();

        $this->subir()->assertCreated();

        Http::assertSentCount(2);
    }

    /**
     * Si la respuesta sigue mal, no queda nada a medias.
     */
    public function test_json_invalido_dos_veces_no_guarda_nada(): void
    {
        $this->fingirPdf(self::TEXTO);
        Http::fake(['*' => Http::response($this->cuerpoGemini('{"semanas": []}'))]);
        $this->actuarComoEstudiante();

        $this->subir()->assertStatus(503);

        Http::assertSentCount(2);
        $this->assertSame(0, Guia::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /**
     * Una frase de inicio inventada por Gemini cuenta como respuesta inválida.
     */
    public function test_rechaza_una_frase_que_no_esta_en_el_pdf(): void
    {
        $this->fingirPdf(self::TEXTO);
        $datos = $this->dosSemanas();
        $datos['semanas'][1]['inicio_texto'] = 'Esta frase no existe en la guía';
        Http::fake(['*' => $this->respuestaGemini($datos)]);
        $this->actuarComoEstudiante();

        $this->subir()->assertStatus(503);

        $this->assertSame(0, Guia::count());
    }

    /**
     * Las semanas deben venir numeradas de forma consecutiva desde 1.
     */
    public function test_rechaza_semanas_mal_numeradas(): void
    {
        $this->fingirPdf(self::TEXTO);
        $datos = $this->dosSemanas();
        $datos['semanas'][1]['numero_semana'] = 5;
        Http::fake(['*' => $this->respuestaGemini($datos)]);
        $this->actuarComoEstudiante();

        $this->subir()->assertStatus(503);
    }

    /**
     * El tope de semanas se valida en PHP, porque la API rechaza maxItems con un 400.
     */
    public function test_el_esquema_no_usa_max_items_y_el_tope_se_valida_en_php(): void
    {
        $this->fingirPdf(self::TEXTO);
        $semanas = array_map(
            fn ($numero) => $this->semana($numero, 'Actividad 1: reconocer', ['Tema']),
            range(1, 53)
        );
        Http::fake(['*' => $this->respuestaGemini(['semanas' => $semanas])]);
        $this->actuarComoEstudiante();

        $this->subir()->assertStatus(503);

        Http::assertSent(function ($peticion) {
            $esquema = $peticion->data()['generationConfig']['responseJsonSchema'] ?? [];

            return $esquema !== [] && ! str_contains(json_encode($esquema), 'maxItems');
        });
    }

    /**
     * Si Gemini no responde, se avisa sin reintentar por respuesta inválida.
     */
    public function test_ia_caida_responde_503(): void
    {
        $this->fingirPdf(self::TEXTO);
        Http::fake(['*' => Http::response('error', 500)]);
        $this->actuarComoEstudiante();

        $this->subir()->assertStatus(503);

        Http::assertSentCount(2); // Modelo principal y modelo de respaldo
        $this->assertSame(0, Guia::count());
    }

    /**
     * Un PDF sin texto se rechaza antes de llamar a Gemini.
     */
    public function test_pdf_escaneado_se_rechaza_sin_llamar_a_gemini(): void
    {
        $this->fingirPdf(null);
        Http::fake();
        $this->actuarComoEstudiante();

        $this->subir()->assertUnprocessable()->assertJsonStructure(['message']);

        Http::assertNothingSent();
    }

    /**
     * Una guía que supera el límite se rechaza en vez de recortarla.
     */
    public function test_guia_demasiado_larga_se_rechaza(): void
    {
        config(['services.gemini.max_caracteres_guia' => 150]);
        $this->fingirPdf(self::TEXTO);
        Http::fake();
        $this->actuarComoEstudiante();

        $this->subir()->assertUnprocessable();

        Http::assertNothingSent();
    }

    /**
     * Subir dos veces el mismo PDF no duplica la guía ni llama de nuevo a Gemini.
     */
    public function test_subir_dos_veces_no_duplica(): void
    {
        $this->fingirPdf(self::TEXTO);
        Http::fake(['*' => $this->respuestaGemini($this->dosSemanas())]);
        $this->actuarComoEstudiante();

        $primera = $this->subir()->assertCreated();

        $this->subir()
            ->assertOk()
            ->assertJsonPath('ya_existia', true)
            ->assertJsonPath('guia.id', $primera->json('guia.id'));

        $this->assertSame(1, Guia::count());
        $this->assertSame(2, GuiaSemana::count());
        Http::assertSentCount(1);
    }

    /**
     * El mismo PDF subido por otro estudiante es una guía distinta.
     */
    public function test_otro_dueno_puede_subir_el_mismo_pdf(): void
    {
        $this->fingirPdf(self::TEXTO);
        Http::fake(['*' => $this->respuestaGemini($this->dosSemanas())]);

        $this->actuarComoEstudiante();
        $this->subir()->assertCreated();

        $this->actuarComoEstudiante();
        $this->subir()->assertCreated();

        $this->assertSame(2, Guia::count());
    }

    /**
     * Al reemplazar el PDF, solo la semana que cambió vuelve a pendiente.
     */
    public function test_reemplazar_solo_regenera_lo_que_cambio(): void
    {
        $this->fingirPdf(self::TEXTO);
        Http::fake(['*' => $this->respuestaGemini($this->dosSemanas())]);
        $this->actuarComoEstudiante();

        $guia = Guia::findOrFail($this->subir()->json('guia.id'));
        $rutaAnterior = $guia->archivo_pdf;
        GuiaSemana::query()->update(['estado_generacion' => 'listo']);

        $this->fingirPdf(self::TEXTO . ' Se agrega un ejercicio nuevo.');

        $this->reemplazar($guia, 'pdf nuevo')
            ->assertOk()
            ->assertJsonPath('semanas_cambiadas', 1);

        $this->assertSame('listo', $guia->semanas()->where('numero_semana', 1)->first()->estado_generacion);
        $this->assertSame('pendiente', $guia->semanas()->where('numero_semana', 2)->first()->estado_generacion);
        $this->assertSame(3, $guia->temas()->count());
        Storage::disk('public')->assertMissing($rutaAnterior);
    }

    /**
     * Borrar una semana con entregas exige confirmación.
     */
    public function test_borrar_semanas_con_entregas_pide_confirmacion(): void
    {
        $this->fingirPdf(self::TEXTO);
        Http::fake(['*' => Http::sequence()
            ->push($this->cuerpoGemini(json_encode($this->dosSemanas())))
            ->push($this->cuerpoGemini(json_encode($this->unaSemana())))
            ->push($this->cuerpoGemini(json_encode($this->unaSemana())))]);
        $estudiante = $this->actuarComoEstudiante();

        $guia = Guia::findOrFail($this->subir()->json('guia.id'));
        $this->crearEntrega($guia->semanas()->where('numero_semana', 2)->first(), $estudiante);

        $this->fingirPdf(self::TEXTO . ' Versión corta.');

        $this->reemplazar($guia, 'pdf corto')
            ->assertStatus(409)
            ->assertJsonPath('semanas', [2]);

        $this->assertSame(2, $guia->semanas()->count());

        $this->reemplazar($guia, 'pdf corto', ['confirmar_eliminacion' => 1])->assertOk();

        $this->assertSame(1, $guia->semanas()->count());
        $this->assertSame(0, EntregaMiniProyecto::count());
    }

    /**
     * Solo el dueño puede reemplazar el PDF.
     */
    public function test_otro_usuario_no_puede_reemplazar(): void
    {
        $this->fingirPdf(self::TEXTO);
        Http::fake(['*' => $this->respuestaGemini($this->dosSemanas())]);
        $this->actuarComoEstudiante();

        $guia = Guia::findOrFail($this->subir()->json('guia.id'));

        $this->actuarComoEstudiante();

        $this->reemplazar($guia, 'pdf ajeno')->assertForbidden();
    }

    /**
     * Inicia sesión con un estudiante nuevo.
     */
    private function actuarComoEstudiante(): User
    {
        $estudiante = User::factory()->create(['rol' => 'estudiante']);
        Sanctum::actingAs($estudiante);

        return $estudiante;
    }

    /**
     * Hace que el extractor devuelva este texto, sin necesitar un PDF real.
     */
    private function fingirPdf(?string $texto): void
    {
        $this->mock(PdfTextExtractor::class, fn ($mock) => $mock->shouldReceive('extraer')->andReturn($texto));
    }

    /**
     * Envía el formulario de subida con un PDF de prueba.
     */
    private function subir(array $datos = [])
    {
        return $this->post('/api/guias', $datos + [
            'nombre'  => 'Guía de prueba',
            'archivo' => UploadedFile::fake()->createWithContent('guia.pdf', '%PDF-1.4 contenido de prueba'),
        ], ['Accept' => 'application/json']);
    }

    /**
     * Reemplaza el PDF igual que lo hará el frontend: POST con _method=PUT.
     */
    private function reemplazar(Guia $guia, string $contenido, array $datos = [])
    {
        return $this->post("/api/guias/{$guia->id}", $datos + [
            '_method' => 'PUT',
            'archivo' => UploadedFile::fake()->createWithContent('guia.pdf', "%PDF-1.4 {$contenido}"),
        ], ['Accept' => 'application/json']);
    }

    /**
     * Crea un mini proyecto con una entrega dentro de la semana indicada.
     */
    private function crearEntrega(GuiaSemana $semana, User $estudiante): void
    {
        $miniProyecto = $semana->miniProyectos()->create([
            'guia_id'                => $semana->guia_id,
            'orden'                  => 1,
            'titulo'                 => 'Mini proyecto',
            'descripcion'            => 'Descripción',
            'requisitos_funcionales' => ['Requisito'],
            'buenas_practicas'       => ['Práctica'],
            'criterios_evaluacion'   => [['criterio' => 'Criterio', 'peso' => 100]],
        ]);

        $miniProyecto->entregas()->create([
            'estudiante_id' => $estudiante->id,
            'repo_url'      => 'https://github.com/ejemplo/repo',
        ]);
    }

    /**
     * Respuesta de ejemplo con dos semanas y tres temas.
     */
    private function dosSemanas(): array
    {
        return ['semanas' => [
            $this->semana(1, 'Actividad 1: reconocer los tipos de datos', ['Variables', 'Tipos de datos']),
            $this->semana(2, 'Actividad 2: construir funciones reutilizables', ['Funciones']),
        ]];
    }

    /**
     * Respuesta de ejemplo con una sola semana.
     */
    private function unaSemana(): array
    {
        return ['semanas' => [
            $this->semana(1, 'Actividad 1: reconocer los tipos de datos', ['Variables']),
        ]];
    }

    /**
     * Arma una semana con el formato del contrato de la Fase 0.
     */
    private function semana(int $numero, string $inicio, array $temas): array
    {
        return [
            'numero_semana' => $numero,
            'titulo'        => "Semana {$numero}",
            'resumen'       => "Resumen de la semana {$numero}.",
            'inicio_texto'  => $inicio,
            'temas'         => array_map(
                fn ($tema) => ['nombre_tema' => $tema, 'descripcion' => "Descripción de {$tema}."],
                $temas
            ),
        ];
    }

    /**
     * Respuesta HTTP falsa de Gemini con este JSON.
     */
    private function respuestaGemini(array $datos)
    {
        return Http::response($this->cuerpoGemini(json_encode($datos)));
    }

    /**
     * Cuerpo con la forma real de la API: el texto va dentro de candidates.
     */
    private function cuerpoGemini(string $texto): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $texto]]]]]];
    }
}
