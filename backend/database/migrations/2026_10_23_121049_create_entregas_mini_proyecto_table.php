<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla entregas_mini_proyecto: repositorio y ultima evaluacion de cada
     * estudiante en un mini proyecto. Esto si es por estudiante.
     */
    public function up(): void
    {
        Schema::create('entregas_mini_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_proyecto_id')->constrained('mini_proyectos')->onDelete('cascade');
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            $table->string('repo_url');
            // Commit evaluado, para poder auditar la entrega
            $table->string('commit_sha', 40)->nullable();
            $table->enum('estado', ['pendiente', 'evaluando', 'evaluado', 'error'])->default('pendiente');
            // Lo calcula el backend con los pesos de los criterios, nunca Gemini
            $table->unsignedTinyInteger('puntaje')->nullable();
            $table->json('evaluacion')->nullable();
            // Total historico de evaluaciones pedidas
            $table->unsignedInteger('intentos')->default(0);
            // Tope diario: se reinicia cuando cambia fecha_intentos
            $table->unsignedInteger('intentos_hoy')->default(0);
            $table->date('fecha_intentos')->nullable();
            $table->timestamp('evaluado_en')->nullable();
            $table->timestamps();

            // Una fila por estudiante: se sobrescribe la ultima evaluacion
            $table->unique(['mini_proyecto_id', 'estudiante_id']);
        });
    }

    /**
     * Elimina la tabla entregas_mini_proyecto si se revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('entregas_mini_proyecto');
    }
};
