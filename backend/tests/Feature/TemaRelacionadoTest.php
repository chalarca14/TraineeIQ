<?php

namespace Tests\Feature;

use App\Models\Guia;
use App\Models\GuiaTema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * En el chat libre, la respuesta sugiere un tema de las guías del estudiante solo si la
 * pregunta de verdad trata de ese tema.
 */
class TemaRelacionadoTest extends TestCase
{
    use RefreshDatabase;

    private User $estudiante;

    /**
     * Crea un estudiante con una guía personal "Ciencia de datos" y cuatro temas.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => 'Respuesta.']]]]]])]);

        $this->estudiante = User::factory()->create(['rol' => 'estudiante']);
        $guia = Guia::create([
            'nombre' => 'Ciencia de datos', 'archivo_pdf' => 'guias/x.pdf',
            'usuario_id' => $this->estudiante->id, 'origen' => 'personal',
        ]);

        foreach (['Estructuras básicas para datos', 'Introducción a Python', 'Control de versiones con Git', 'Análisis de datos con Python'] as $orden => $nombre) {
            GuiaTema::create(['guia_id' => $guia->id, 'nombre_tema' => $nombre, 'descripcion' => '.', 'orden' => $orden + 1]);
        }
    }

    /**
     * El caso visto en producción: "para" ya no relaciona una pregunta con un tema ajeno.
     */
    public function test_las_palabras_comunes_no_relacionan_temas(): void
    {
        $this->assertSame('Respuesta.', $this->preguntar('¿Qué es la recursividad y para qué sirve?'));
    }

    /**
     * Las siglas de 3 letras cuentan: "Git" lleva al tema de Git, no al de "para datos".
     */
    public function test_las_siglas_de_tres_letras_cuentan(): void
    {
        $this->assertStringContainsString('tema "Control de versiones con Git"', $this->preguntar('¿Qué es Git y para qué sirve?'));
    }

    /**
     * Las tildes no parten las palabras: "estructuras" y "datos" coinciden con el tema.
     */
    public function test_las_tildes_no_parten_las_palabras(): void
    {
        $this->assertStringContainsString('tema "Estructuras básicas para datos"', $this->preguntar('¿Qué estructuras de datos existen?'));
    }

    /**
     * Gana el tema que comparte más palabras con la pregunta, no el primero de la lista.
     */
    public function test_gana_el_tema_con_mas_coincidencias(): void
    {
        // "datos" coincide con dos temas y "python" con otros dos; solo uno tiene las tres palabras
        $this->assertStringContainsString('tema "Análisis de datos con Python"', $this->preguntar('¿Cómo hago análisis de datos en Python?'));
    }

    /**
     * Envía una pregunta al chat libre del estudiante y devuelve el texto de la respuesta.
     */
    private function preguntar(string $pregunta): string
    {
        Sanctum::actingAs($this->estudiante);
        $conversacion = $this->postJson('/api/conversaciones', ['tipo' => 'libre'])->assertCreated()->json('id');

        return $this->postJson("/api/conversaciones/{$conversacion}/mensajes", ['contenido' => $pregunta])
            ->assertOk()
            ->json('respuesta_ia.contenido');
    }
}
