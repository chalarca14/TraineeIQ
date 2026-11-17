<?php

namespace Tests\Feature;

use App\Exceptions\GuiaNoProcesableException;
use App\Models\GuiaSemana;
use App\Models\User;
use App\Services\ExcelTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Guías en Excel (.xlsx), como el pacto pedagógico o la planeación del trimestre del SENA.
 * Los Excel se arman aquí mismo con PhpSpreadsheet, sin archivos guardados en el repositorio.
 */
class GuiaExcelTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> Archivos temporales que se borran al terminar cada prueba */
    private array $temporales = [];

    /**
     * Todas las pruebas usan un disco falso para no escribir archivos reales.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Borra los Excel temporales creados por la prueba.
     */
    protected function tearDown(): void
    {
        foreach ($this->temporales as $ruta) {
            @unlink($ruta);
        }
        parent::tearDown();
    }

    /**
     * Las filas de la misma semana se juntan aunque estén en hojas distintas, y la información
     * de arriba de la tabla queda al principio.
     */
    public function test_agrupa_por_semana_las_filas_de_todas_las_hojas(): void
    {
        $texto = $this->extraer($this->libroPlaneacion());

        $this->assertMatchesRegularExpression(
            '/^INFORMACIÓN GENERAL\n.*Ciencia de datos con Python.*\nSemana 1\n.*\nSemana 2\n/su',
            $texto
        );

        // La actividad de la hoja "Actividades" queda dentro de la semana 2, no al final del texto
        $semana2 = $this->bloque($texto, 'Semana 2');
        $this->assertStringContainsString("FORMATO\n\nConocimientos: Leer archivos CSV con pandas y revisar su estructura.", $semana2);
        $this->assertStringContainsString("ACTIVIDADES\n\nActividad: Cargar un dataset de ventas y describir sus columnas.", $semana2);
        $this->assertStringNotContainsString('Cargar un dataset', $this->bloque($texto, 'Semana 1'));
    }

    /**
     * Una celda combinada de varias filas se lee una sola vez en su semana, y una fila que
     * repite lo que ya dijo otra hoja de la misma semana no se duplica.
     */
    public function test_celdas_combinadas_y_filas_repetidas_no_duplican_texto(): void
    {
        $texto = $this->extraer($this->libroPlaneacion());
        $semana1 = $this->bloque($texto, 'Semana 1');

        $this->assertSame(1, substr_count($texto, 'Comprender qué es la ciencia de datos, su ciclo de vida y cómo apoya la toma de decisiones.'));
        // "Temas" repite los conocimientos de "Formato": solo aporta su enfoque
        $this->assertStringContainsString("TEMAS\n\nEnfoque: Contexto y ética de los datos", $semana1);
    }

    /**
     * Forma que la pantalla de la guía muestra ordenada (utils/textoGuia.js del frontend): un título
     * en mayúsculas por hoja, un dato por párrafo, y los títulos de columna en mayúsculas pasan a
     * "Acciones a desarrollar". Una fila sin títulos (el encabezado del formato) va en una línea.
     */
    public function test_el_texto_tiene_forma_de_secciones_y_etiquetas(): void
    {
        $texto = $this->extraer($this->libroPlaneacion());

        $this->assertStringContainsString(
            "FORMATO\n\nConocimientos: Comprender qué es la ciencia de datos, su ciclo de vida y cómo apoya la toma de decisiones.\n\n"
            . 'Acciones a desarrollar: Analizar casos reales de decisiones tomadas con datos en el SENA.',
            $texto
        );
        $this->assertStringContainsString('SERVICIO NACIONAL DE APRENDIZAJE - SENA · 3/02/2026', $texto);
    }

    /**
     * PlanEvaluacion (de la lista de .env) y las hojas ocultas no se leen.
     */
    public function test_ignora_plan_evaluacion_y_las_hojas_ocultas(): void
    {
        $texto = $this->extraer($this->libroPlaneacion());

        $this->assertStringNotContainsString('Programación orientada a objetos', $texto);
        $this->assertStringNotContainsString('Borrador viejo', $texto);
    }

    /**
     * Fechas y porcentajes salen como se ven en Excel, no como el número que guarda.
     */
    public function test_fechas_y_porcentajes_salen_como_en_excel(): void
    {
        $texto = $this->extraer($this->libroPlaneacion());

        $this->assertStringContainsString('3/02/2026', $texto);
        $this->assertStringContainsString('Peso: 25%', $texto);
    }

    /**
     * Una hoja sin columna de semana pero con la semana en su nombre va a esa semana.
     */
    public function test_hoja_con_la_semana_en_el_nombre_va_a_esa_semana(): void
    {
        $texto = $this->extraer($this->libroPlaneacion());

        $this->assertStringContainsString(
            "SEMANA 2 - DETALLE\n\nPaso: 2.1\n\nContenido: Abrir el notebook y cargar el CSV de práctica.",
            $this->bloque($texto, 'Semana 2')
        );
    }

    /**
     * Si el Excel no tiene semanas, se lee hoja por hoja y Gemini lo organiza como un PDF.
     */
    public function test_sin_semanas_se_lee_hoja_por_hoja(): void
    {
        $libro = new Spreadsheet();
        $this->llenar($libro->getActiveSheet()->setTitle('Temario'), [
            ['Tema', 'Descripción'],
            ['Variables', 'Qué es una variable y cómo se declara en Python.'],
            ['Funciones', 'Funciones con parámetros y valor de retorno.'],
        ]);

        $this->assertSame(
            "TEMARIO\n\n"
            . "Tema: Variables\n\nDescripción: Qué es una variable y cómo se declara en Python.\n\n"
            . "Tema: Funciones\n\nDescripción: Funciones con parámetros y valor de retorno.",
            $this->extraer($libro)
        );
    }

    /**
     * Un Excel con demasiadas celdas se rechaza antes de cargarlo, para no gastar la memoria.
     */
    public function test_un_excel_con_demasiadas_celdas_se_rechaza(): void
    {
        config(['services.guia_excel.max_celdas' => 10]);

        $this->expectException(GuiaNoProcesableException::class);
        $this->extraer($this->libroPlaneacion());
    }

    /**
     * Flujo completo: subir el Excel crea las semanas, y cada semana guarda su propio texto.
     */
    public function test_subir_un_excel_crea_las_semanas(): void
    {
        Http::fake(['*' => Http::response($this->cuerpoGemini(['semanas' => [
            $this->semana(1, 'Semana 1'),
            $this->semana(2, 'Semana 2'),
        ]]))]);
        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));

        $this->post('/api/guias', [
            'nombre'  => 'Planeación de Ciencia de datos',
            'archivo' => $this->subida($this->libroPlaneacion()),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('guia.semanas_totales', 2);

        [$semana1, $semana2] = GuiaSemana::orderBy('numero_semana')->get()->all();

        // La información general queda en la semana 1, como la portada de un PDF
        $this->assertStringStartsWith('INFORMACIÓN GENERAL', $semana1->contenido_texto);
        $this->assertStringContainsString('ciclo de vida', $semana1->contenido_texto);
        $this->assertStringStartsWith('Semana 2', $semana2->contenido_texto);
        $this->assertStringContainsString('Cargar un dataset de ventas', $semana2->contenido_texto);

        // Dividir se pide con pensamiento bajo, para que no pase el tiempo límite
        Http::assertSent(fn ($peticion) => ($peticion['generationConfig']['thinkingConfig']['thinkingLevel'] ?? null) === 'low');
    }

    /**
     * Un archivo que no es PDF ni Excel se rechaza con el mensaje en español.
     */
    public function test_rechaza_archivos_que_no_son_pdf_ni_excel(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));

        $this->post('/api/guias', [
            'nombre'  => 'Guía',
            'archivo' => UploadedFile::fake()->createWithContent('guia.txt', 'texto plano'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['archivo' => 'La guía debe ser un PDF o un Excel (.xlsx).']);
    }

    /**
     * El Excel tiene un límite de peso menor que el PDF.
     */
    public function test_rechaza_un_excel_mas_pesado_que_el_limite(): void
    {
        config(['services.guia_excel.max_kb' => 1]);
        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));

        $this->post('/api/guias', [
            'nombre'  => 'Guía',
            'archivo' => $this->subida($this->libroPlaneacion()),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['archivo' => 'El Excel no puede pesar más de 1 KB.']);
    }

    /**
     * Un pacto pedagógico pequeño con lo típico: encabezado, títulos combinados, semanas con
     * celdas combinadas, una hoja que repite datos, otra de actividades, una vieja y una oculta.
     */
    private function libroPlaneacion(): Spreadsheet
    {
        $libro = new Spreadsheet();

        $formato = $libro->getActiveSheet()->setTitle('Formato');
        $this->llenar($formato, [
            ['SERVICIO NACIONAL DE APRENDIZAJE - SENA'],
            ['ACTIVIDAD DE PROYECTO: Ciencia de datos con Python'],
            [],
            ['SEMANA', 'Conocimientos', 'ACCIONES A DESARROLLAR'],
            ['PROGRAMADA'],
            ['Semana 01', 'Comprender qué es la ciencia de datos, su ciclo de vida y cómo apoya la toma de decisiones.', 'Analizar casos reales de decisiones tomadas con datos en el SENA.'],
            [],
            ['Semana 02', 'Leer archivos CSV con pandas y revisar su estructura.', 'Explorar un dataset y documentar los hallazgos iniciales.'],
        ]);
        // Títulos combinados con la fila de abajo, y semana 1 ocupando dos filas, como en el RP33
        $formato->mergeCells('A4:A5');
        $formato->mergeCells('B6:B7');
        $formato->mergeCells('C6:C7');
        $formato->setCellValue('D1', Date::PHPToExcel(mktime(0, 0, 0, 2, 3, 2026)));
        $formato->getStyle('D1')->getNumberFormat()->setFormatCode('d/mm/yyyy');

        $this->llenar($libro->createSheet()->setTitle('PlanEvaluacion'), [
            ['Evaluación', 'Actividad'],
            ['Semana 01', 'Programación orientada a objetos'],
            ['Semana 02', 'Programación orientada a objetos avanzada'],
        ]);

        $this->llenar($libro->createSheet()->setTitle('Temas'), [
            ['Semana', 'Enfoque', 'Conocimientos'],
            ['Semana 01', 'Contexto y ética de los datos', 'Comprender qué es la ciencia de datos, su ciclo de vida y cómo apoya la toma de decisiones.'],
            ['Semana 02', 'Python para datos', 'Leer archivos CSV con pandas y revisar su estructura.'],
        ]);

        $actividades = $libro->createSheet()->setTitle('Actividades');
        $this->llenar($actividades, [
            ['Semana', 'Actividad', 'Peso'],
            ['Semana 02', 'Cargar un dataset de ventas y describir sus columnas.', 0.25],
            ['Semana 01', 'Formular tres preguntas que se puedan responder con datos.', 0.1],
        ]);
        $actividades->getStyle('C2:C3')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE);

        $this->llenar($libro->createSheet()->setTitle('Semana 2 - Detalle'), [
            ['Paso', 'Contenido'],
            ['2.1', 'Abrir el notebook y cargar el CSV de práctica.'],
        ]);

        $oculta = $libro->createSheet()->setTitle('Borrador');
        $this->llenar($oculta, [['Semana', 'Nota'], ['Semana 01', 'Borrador viejo'], ['Semana 02', 'Borrador viejo 2']]);
        $oculta->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        return $libro;
    }

    /**
     * Escribe las filas en la hoja desde A1. Una fila vacía deja el renglón en blanco.
     */
    private function llenar(Worksheet $hoja, array $filas): void
    {
        $hoja->fromArray($filas, null, 'A1', true);
    }

    /**
     * Guarda el libro en un .xlsx temporal y devuelve su ruta.
     */
    private function guardar(Spreadsheet $libro): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'guia') . '.xlsx';
        (new Xlsx($libro))->save($ruta);
        $this->temporales[] = $ruta;

        return $ruta;
    }

    /**
     * Pasa el libro por el lector de Excel real.
     */
    private function extraer(Spreadsheet $libro): string
    {
        return (string) app(ExcelTextExtractor::class)->extraer($this->guardar($libro));
    }

    /**
     * El libro como archivo subido desde el formulario.
     */
    private function subida(Spreadsheet $libro): UploadedFile
    {
        return new UploadedFile($this->guardar($libro), 'planeacion.xlsx', null, null, true);
    }

    /**
     * El bloque de una semana: desde su título hasta el título de la semana siguiente.
     */
    private function bloque(string $texto, string $titulo): string
    {
        preg_match('/^' . preg_quote($titulo, '/') . '\n(.*?)(?=\nSemana \d+\n|\z)/msu', $texto, $m);

        return $m[1] ?? '';
    }

    /**
     * Semana con el formato del contrato de la Fase 0.
     */
    private function semana(int $numero, string $inicio): array
    {
        return [
            'numero_semana' => $numero,
            'titulo'        => "Semana {$numero}",
            'resumen'       => "Resumen de la semana {$numero}.",
            'inicio_texto'  => $inicio,
            'temas'         => [['nombre_tema' => "Tema {$numero}", 'descripcion' => 'Descripción del tema.']],
        ];
    }

    /**
     * Cuerpo con la forma real de la API de Gemini: el JSON va dentro de candidates.
     */
    private function cuerpoGemini(array $datos): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => json_encode($datos)]]]]]];
    }
}
