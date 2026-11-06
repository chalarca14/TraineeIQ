<?php

namespace Tests\Feature;

use App\Models\EntregaMiniProyecto;
use App\Models\Grupo;
use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Models\MiniProyecto;
use App\Models\Recomendacion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EsquemaSemanasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El seeder deja las relaciones de la guía listas para usar.
     */
    public function test_las_relaciones_de_semanas_responden(): void
    {
        $this->seed(DatabaseSeeder::class);

        $guia = Guia::where('nombre', 'Guia de Introduccion')->firstOrFail();
        $semana = $guia->semanas()->firstOrFail();
        $miniProyecto = $semana->miniProyectos()->firstOrFail();

        $this->assertCount(4, $guia->semanas);
        $this->assertCount(4, $semana->recomendaciones);
        $this->assertCount(3, $semana->miniProyectos);
        $this->assertCount(1, $semana->temas);
        $this->assertSame('basico', $semana->recomendaciones->first()->nivel);
        $this->assertIsArray($semana->recomendaciones->first()->contenido);
        $this->assertSame($semana->id, $miniProyecto->semana->id);
        $this->assertIsArray($miniProyecto->criterios_evaluacion);
        $this->assertSame(100, array_sum(array_column($miniProyecto->criterios_evaluacion, 'peso')));

        $entrega = $miniProyecto->entregas()->firstOrFail();
        $this->assertSame('estudiante1@traineeiq.com', $entrega->estudiante->email);
        $this->assertSame($miniProyecto->id, $entrega->miniProyecto->id);
        $this->assertCount(1, $entrega->estudiante->entregas);
    }

    /**
     * Una guía no puede repetir número de semana.
     */
    public function test_no_se_repite_el_numero_de_semana(): void
    {
        $this->seed(DatabaseSeeder::class);
        $semana = GuiaSemana::firstOrFail();

        $this->expectException(QueryException::class);

        GuiaSemana::create([
            'guia_id'         => $semana->guia_id,
            'numero_semana'   => $semana->numero_semana,
            'titulo'          => 'Repetida',
            'resumen'         => 'Repetida',
            'contenido_texto' => 'Repetida',
            'hash_contenido'  => hash('sha256', 'Repetida'),
        ]);
    }

    /**
     * Una semana no puede repetir orden en sus recomendaciones.
     */
    public function test_no_se_repite_el_orden_de_una_recomendacion(): void
    {
        $this->seed(DatabaseSeeder::class);
        $recomendacion = Recomendacion::firstOrFail();

        $this->expectException(QueryException::class);

        Recomendacion::create([
            'guia_id'        => $recomendacion->guia_id,
            'guia_semana_id' => $recomendacion->guia_semana_id,
            'orden'          => $recomendacion->orden,
            'nivel'          => 'basico',
            'contenido'      => ['titulo' => 'Repetida', 'descripcion' => 'Repetida'],
        ]);
    }

    /**
     * Un estudiante solo tiene una entrega por mini proyecto.
     */
    public function test_solo_hay_una_entrega_por_estudiante_y_mini_proyecto(): void
    {
        $this->seed(DatabaseSeeder::class);
        $entrega = EntregaMiniProyecto::firstOrFail();

        $this->expectException(QueryException::class);

        EntregaMiniProyecto::create([
            'mini_proyecto_id' => $entrega->mini_proyecto_id,
            'estudiante_id'    => $entrega->estudiante_id,
            'repo_url'         => 'https://github.com/ejemplo/otro',
        ]);
    }

    /**
     * Un estudiante no puede inscribirse dos veces al mismo grupo.
     */
    public function test_un_estudiante_no_se_inscribe_dos_veces(): void
    {
        $this->seed(DatabaseSeeder::class);
        $grupo = Grupo::firstOrFail();
        $estudiante = $grupo->estudiantes()->firstOrFail();

        $this->expectException(QueryException::class);

        $grupo->estudiantes()->attach($estudiante->id);
    }

    /**
     * Borrar una guía borra en cascada sus semanas y su contenido.
     */
    public function test_borrar_la_guia_borra_su_contenido(): void
    {
        $this->seed(DatabaseSeeder::class);

        Guia::where('nombre', 'Guia de Introduccion')->firstOrFail()->delete();

        $this->assertSame(1, GuiaSemana::count());
        $this->assertSame(3, MiniProyecto::count());
        $this->assertSame(0, EntregaMiniProyecto::count());
    }
}
