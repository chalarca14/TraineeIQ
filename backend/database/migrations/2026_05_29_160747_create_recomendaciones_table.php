<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla recomendaciones: contenido generado por Gemini para una semana.
     * Pertenece a la guia, no al estudiante: todo el grupo ve las mismas.
     */
    public function up(): void
    {
        Schema::create('recomendaciones', function (Blueprint $table) {
            //Llave primaria autoincremental
            $table->id();
            //Llave foranea de la guia
            $table->foreignId('guia_id')->constrained('guias')->onDelete('cascade');
            //Llave foranea de la semana a la que corresponde la recomendacion
            $table->foreignId('guia_semana_id')->constrained('guia_semanas')->onDelete('cascade');
            //Posicion dentro de la semana; permite regenerar sin duplicar
            $table->unsignedTinyInteger('orden');
            $table->enum('nivel', ['basico', 'recomendado', 'importante', 'avanzado'])->default('recomendado');
            //Contenido JSON con el titulo y la descripcion
            $table->json('contenido');
            //Fechas de creacion y actualizacion automaticas
            $table->timestamps();

            $table->unique(['guia_semana_id', 'orden']);
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
