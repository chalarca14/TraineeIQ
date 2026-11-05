<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla mini_proyectos: proyectos generados por Gemini para una semana.
     * Pertenecen a la guia, no al estudiante.
     */
    public function up(): void
    {
        Schema::create('mini_proyectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_id')->constrained('guias')->onDelete('cascade');
            $table->foreignId('guia_semana_id')->constrained('guia_semanas')->onDelete('cascade');
            // Posicion dentro de la semana; permite regenerar sin borrar las entregas
            $table->unsignedTinyInteger('orden');
            $table->string('titulo');
            $table->text('descripcion');
            $table->enum('dificultad', ['basico', 'intermedio', 'avanzado'])->default('basico');
            // Instrucciones completas paso a paso
            $table->longText('contenido')->nullable();
            $table->json('requisitos_funcionales');
            $table->json('buenas_practicas');
            // Lista de criterios con su peso; los pesos suman 100
            $table->json('criterios_evaluacion');
            $table->timestamps();

            $table->unique(['guia_semana_id', 'orden']);
        });
    }

    /**
     * Elimina la tabla mini_proyectos si se revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('mini_proyectos');
    }
};
