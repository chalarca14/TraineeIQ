<?php

namespace Database\Seeders;

use App\Models\EntregaMiniProyecto;
use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Crea semanas de prueba con todos los estados de generacion, para poder
 * probar las pantallas sin gastar llamadas a Gemini.
 */
class ContenidoSemanasSeeder extends Seeder
{
    /**
     * Llena la guia de grupo y crea una guia personal del estudiante uno.
     */
    public function run(): void
    {
        $estudiante = User::where('email', 'estudiante1@traineeiq.com')->firstOrFail();
        $guiaGrupo = Guia::where('nombre', 'Guia de Introduccion')->firstOrFail();

        $semana1 = $this->crearSemana($guiaGrupo, 1, 'Fundamentos de JavaScript', 'listo');
        $this->crearSemana($guiaGrupo, 2, 'Funciones', 'listo');
        $this->crearSemana($guiaGrupo, 3, 'Arreglos y objetos', 'pendiente');
        $this->crearSemana($guiaGrupo, 4, 'Consumo de APIs', 'error', 'La IA devolvió un formato inválido.');

        $guiaPersonal = Guia::create([
            'nombre'          => 'Guia personal de Vue',
            'origen'          => 'personal',
            'usuario_id'      => $estudiante->id,
            'archivo_pdf'     => 'guias/demo-personal.pdf',
            'semanas_totales' => 1,
        ]);
        $this->crearSemana($guiaPersonal, 1, 'Reactividad en Vue', 'listo');

        $this->crearEntregaEvaluada($semana1, $estudiante);
    }

    /**
     * Crea una semana con su tema y, si esta lista, con su contenido.
     */
    private function crearSemana(Guia $guia, int $numero, string $titulo, string $estado, ?string $error = null): GuiaSemana
    {
        $texto = "Texto de prueba de la semana {$numero}: {$titulo}.";

        $semana = GuiaSemana::create([
            'guia_id'           => $guia->id,
            'numero_semana'     => $numero,
            'titulo'            => $titulo,
            'resumen'           => "Resumen de prueba sobre {$titulo}.",
            'contenido_texto'   => $texto,
            'hash_contenido'    => hash('sha256', $texto),
            'estado_generacion' => $estado,
            'error_mensaje'     => $error,
        ]);

        $semana->temas()->create([
            'guia_id'     => $guia->id,
            'nombre_tema' => $titulo,
            'descripcion' => "Tema de prueba sobre {$titulo}.",
            'orden'       => 1,
        ]);

        if ($estado === 'listo') {
            $this->crearRecomendaciones($semana);
            $this->crearMiniProyectos($semana);
        }

        return $semana;
    }

    /**
     * Crea las 4 recomendaciones de una semana, una por cada nivel.
     */
    private function crearRecomendaciones(GuiaSemana $semana): void
    {
        $niveles = ['basico', 'recomendado', 'importante', 'avanzado'];

        foreach ($niveles as $indice => $nivel) {
            $semana->recomendaciones()->create([
                'guia_id'   => $semana->guia_id,
                'orden'     => $indice + 1,
                'nivel'     => $nivel,
                'contenido' => [
                    'titulo'      => "Recomendación {$nivel}",
                    'descripcion' => "Actividad de prueba de nivel {$nivel} sobre {$semana->titulo}.",
                ],
            ]);
        }
    }

    /**
     * Crea los 3 mini proyectos de una semana, uno por cada dificultad.
     */
    private function crearMiniProyectos(GuiaSemana $semana): void
    {
        $dificultades = ['basico', 'intermedio', 'avanzado'];

        foreach ($dificultades as $indice => $dificultad) {
            $semana->miniProyectos()->create([
                'guia_id'                => $semana->guia_id,
                'orden'                  => $indice + 1,
                'titulo'                 => "Mini proyecto {$dificultad}",
                'descripcion'            => "Proyecto de prueba sobre {$semana->titulo}.",
                'dificultad'             => $dificultad,
                'contenido'              => "1. Crea el proyecto.\n2. Implementa los requisitos.\n3. Sube el código a GitHub.",
                'requisitos_funcionales' => ['Cumple la función principal', 'Valida los datos de entrada'],
                'buenas_practicas'       => ['Usa nombres descriptivos'],
                'criterios_evaluacion'   => [
                    ['criterio' => 'La función principal sirve', 'peso' => 60],
                    ['criterio' => 'Validación de datos', 'peso' => 20],
                    ['criterio' => 'Claridad del código', 'peso' => 20],
                ],
            ]);
        }
    }

    /**
     * Crea una entrega ya evaluada, con el formato del contrato de la Fase 0.
     */
    private function crearEntregaEvaluada(GuiaSemana $semana, User $estudiante): void
    {
        EntregaMiniProyecto::create([
            'mini_proyecto_id' => $semana->miniProyectos()->firstOrFail()->id,
            'estudiante_id'    => $estudiante->id,
            'repo_url'         => 'https://github.com/ejemplo/mini-proyecto',
            'estado'           => 'evaluado',
            'puntaje'          => 74,
            'evaluacion'       => [
                'resumen'   => 'Cumple lo principal, pero falta validar la entrada.',
                'puntaje'   => 74,
                'criterios' => [
                    ['criterio' => 'La función principal sirve', 'peso' => 60, 'cumplimiento' => 100, 'comentario' => 'Funciona como se pide.'],
                    ['criterio' => 'Validación de datos', 'peso' => 20, 'cumplimiento' => 0, 'comentario' => 'No valida la entrada.'],
                    ['criterio' => 'Claridad del código', 'peso' => 20, 'cumplimiento' => 70, 'comentario' => 'Hay código repetido.'],
                ],
                'requisitos' => [
                    ['requisito' => 'Cumple la función principal', 'cumple' => true, 'comentario' => 'Implementado.'],
                    ['requisito' => 'Valida los datos de entrada', 'cumple' => false, 'comentario' => 'Falta la validación.'],
                ],
                'buenas_practicas' => [
                    ['practica' => 'Usa nombres descriptivos', 'cumple' => true, 'comentario' => 'Los nombres son claros.'],
                ],
                'sugerencias' => ['Valida los datos antes de usarlos.'],
            ],
            'intentos'       => 1,
            'intentos_hoy'   => 1,
            'fecha_intentos' => now()->toDateString(),
            'evaluado_en'    => now(),
        ]);
    }
}
