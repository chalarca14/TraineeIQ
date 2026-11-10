<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla guia_semanas: cada guia se divide en semanas, y cada semana guarda
     * su texto y el estado de generacion de su contenido con la IA.
     */
    public function up(): void
    {
        Schema::create('guia_semanas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_id')->constrained('guias')->onDelete('cascade');
            $table->unsignedInteger('numero_semana');
            $table->string('titulo');
            $table->text('resumen');
            // Texto real del PDF que corresponde a esta semana
            $table->longText('contenido_texto');
            // Hash de contenido_texto: si no cambia, la semana no se regenera
            $table->string('hash_contenido', 64);
            $table->enum('estado_generacion', ['pendiente', 'generando', 'listo', 'error'])->default('pendiente');
            $table->text('error_mensaje')->nullable();
            $table->timestamps();

            // Una guia no puede tener dos veces la misma semana
            $table->unique(['guia_id', 'numero_semana']);
        });
    }

    /**
     * Elimina la tabla guia_semanas si se revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('guia_semanas');
    }
};
