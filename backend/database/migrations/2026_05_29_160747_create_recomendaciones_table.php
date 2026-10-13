<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla recomendaciones: donde se guardan las respuestas de Gemini
     */
    public function up(): void
    {
        Schema::create('recomendaciones', function (Blueprint $table) {
            //Llave primaria autoincremental
            $table->id();

            //Llave foranea del estudiante en la tabla users
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');

            //Llave foranea de la guia
            $table->foreignId('guia_id')->constrained('guias')->onDelete('cascade');

            //Semana a la que corresponde la recomendacion
            $table->integer('semana');

            //Contenido JSON con la respuesta de Gemini
            $table->json('contenido');

            //Fechas de creacion y actualizacion automaticas
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla recomendaciones si se revierte la migracion
     */
    public function down(): void
    {
        Schema::dropIfExists('recomendaciones');
    }
};
