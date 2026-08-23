<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Grupo;
use App\Models\Guia;
use App\Models\GuiaTema;
use App\Models\Recomendacion;
use App\Models\MiniProyecto;
use App\Models\ConversacionIa;
use App\Models\MensajeIa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuarios (Instructor y Estudiantes)
        $instructor = User::create([
            'name' => 'Instructor Demo',
            'email' => 'instructor@traineeiq.com',
            'password' => Hash::make('password123'),
            'rol' => 'instructor',
            'modo_preferido' => 'grupo',
        ]);

        $estudiante1 = User::create([
            'name' => 'Carlos Pérez',
            'email' => 'carlos@traineeiq.com',
            'password' => Hash::make('password123'),
            'rol' => 'estudiante',
            'modo_preferido' => 'grupo',
        ]);

        $estudiante2 = User::create([
            'name' => 'María Gómez',
            'email' => 'maria@traineeiq.com',
            'password' => Hash::make('password123'),
            'rol' => 'estudiante',
            'modo_preferido' => 'personal',
        ]);

        // 2. Grupo
        $grupo = Grupo::create([
            'nombre' => 'Desarrollo Backend Laravel 2026',
            'descripcion' => 'Grupo de entrenamiento intensivo en backend',
            'instructor_id' => $instructor->id,
            'codigo_acceso' => 'TRQ2026',
        ]);

        // 3. Relación Estudiante-Grupo
        DB::table('estudiante_grupo')->insert([
            [
                'estudiante_id' => $estudiante1->id,
                'grupo_id' => $grupo->id,
                'semana_actual' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // 4. Guía de Aprendizaje
        $guia = Guia::create([
            'nombre' => 'Guía de Fundamentos de Bases de Datos y ORM',
            'grupo_id' => $grupo->id,
            'usuario_id' => $instructor->id,
            'origen' => 'grupo',
            'archivo_pdf' => 'guias/fundamentos_laravel.pdf',
            'semanas_totales' => 4,
        ]);

        // 5. Temas de la Guía
        $tema1 = GuiaTema::create([
            'guia_id' => $guia->id,
            'nombre_tema' => 'Diseño de Tablas y Migraciones',
            'descripcion' => 'Conceptos básicos de DDL y estructura relacional',
            'orden' => 1,
        ]);

        $tema2 = GuiaTema::create([
            'guia_id' => $guia->id,
            'nombre_tema' => 'Consultas y Relaciones Eloquent',
            'descripcion' => 'Uso de DML, llaves foráneas y relaciones en Laravel',
            'orden' => 2,
        ]);

        // 6. Recomendación de Estudio
        Recomendacion::create([
            'estudiante_id' => $estudiante1->id,
            'guia_id' => $guia->id,
            'tema_id' => $tema1->id,
            'semana' => 1,
            'nivel' => 'intermedio',
            'contenido' => json_encode([
                'resumen' => 'Repasa los tipos de datos en MySQL/MariaDB y cómo declarar llaves foráneas.',
                'recursos' => ['https://laravel.com/docs/migrations', 'https://dev.mysql.com/doc/']
            ]),
        ]);

        // 7. Mini Proyecto
        MiniProyecto::create([
            'guia_id' => $guia->id,
            'tema_id' => $tema1->id,
            'titulo' => 'Sistema de Gestión de Tareas',
            'descripcion' => 'Crear las migraciones para un modelo de tareas con usuarios asociados.',
            'dificultad' => 'facil',
            'contenido' => 'Debes crear una tabla tareas con título, descripción y estado.',
        ]);

        // 8. Conversación con la IA
        $conversacion = ConversacionIa::create([
            'estudiante_id' => $estudiante1->id,
            'guia_id' => $guia->id,
            'tipo' => 'guiada',
            'titulo' => 'Duda sobre Prepared Statements y SQL Injection',
        ]);

        // 9. Mensajes de la Conversación
        MensajeIa::create([
            'conversacion_id' => $conversacion->id,
            'emisor' => 'usuario',
            'contenido' => '¿Para qué sirven las Prepared Statements en las consultas a base de datos?',
        ]);

        MensajeIa::create([
            'conversacion_id' => $conversacion->id,
            'emisor' => 'ia',
            'contenido' => 'Las Prepared Statements separan el código SQL de los datos enviados por el usuario, evitando ataques de inyección SQL (SQL Injection).',
        ]);
    }
}