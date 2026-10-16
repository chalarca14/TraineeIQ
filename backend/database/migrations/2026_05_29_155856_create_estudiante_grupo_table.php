<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla estudiante_grupo que relaciona estudiantes con grupos
     */
    public function up(): void
    {
        Schema::create('estudiante_grupo', function (Blueprint $table) {
            //lLave primaria autoincremental
            $table->id();

            //Llave foreana de estudiantes en la tabla users
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');

            //Llave foranea que referencia al grupo
            $table->foreignId('grupo_id')->constrained('grupos')->onDelete('cascade');

            //Semana actual en la que se encuentra el estudiante
            $table->integer('semana_actual')->default(1);

            //Fecha de creacion y actualizacion automatica
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla la tabla estiante_grupo si se revierte la migracion
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiante_grupo');
    }
};
