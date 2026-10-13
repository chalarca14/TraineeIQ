<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TraineeIQSeeder extends Seeder
{
    public function run(): void{
        //Crear instructor
        $instructorId = DB::table('users')->insertGetId([
            'name'          => 'Instructor Demo',
            'email'         => 'instructor@traineeiq.com',
            'password'      => Hash::make('password123'),
            'rol'           => 'instructor',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        //Crear estudiantes
        $estudiante1Id = DB::table('users')->insertGetId([
            'name'          => 'Estudiante uno',
            'email'         => 'estuadiante1@traineeiq.com',
            'password'      => Hash::make('password123'),
            'rol'           => 'estudiante',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $estudiante2Id = DB::table('users')->insertGetId([
            'name'          => 'Estudiente Dos',
            'email'         => 'estudiante2@traineeiq.com',
            'password'      => Hash::make('password123'),
            'rol'           => 'estudiante',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        //Crear grupo
        $grupoId =DB::table('grupos')->insertGetId([
            'nombre'        => 'Grupo Demo SENA',
            'descripcion'   => 'Grupo de prueba para desarrollo',
            'instructor_id' => $instructorId,
            'codigo_acceso' => 'SENA2025',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        //Crear guia
        $guiaId = DB::table('guias')->insertGetId([
            'nombre' => 'Guia de Introduccion',
            'grupo_id' => $grupoId,
            'archivo_pdf' => 'guias/demo.pdf',
            'semanas_totales' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        //Vincular estudiantes al grupo
        DB::table('estudiante_grupo')->insert([
            'estudiante_id'     => $estudiante1Id,
            'grupo_id'          => $grupoId,
            'semana_actual'     => 1,
            'created_at'        => now(),
            'updated_at'        => now(),
        ],
        [
            'estudiante_id' => $estudiante2Id,
            'grupo_id' => $grupoId,
            'semana_actual' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
