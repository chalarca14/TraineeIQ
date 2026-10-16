<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla de profesores: guardar grupos de aprendizaje 
     */
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            //Llave primaria
            $table->id();
            //Nombre del grupo
            $table->string('nombre');
            //Descripcion opcional del grupo
            $table->text('descripcion')->nullable();
            //Llave foranea que referencia al instructor en la tabla users
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            //codigo unico para que los estudiantes se unan al grupo
            $table->string('codigo_acceso')->unique();
            //Fechas de creacion y actualizacion automaticas
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla grupos si se revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
