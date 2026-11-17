<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guías detalladas que el instructor adjunta a una semana de la guía principal. Solo se
     * guarda su texto (como con las guías: Render no conserva archivos). La IA las usa, junto
     * con la planeación, para las recomendaciones y los mini proyectos de esa semana.
     */
    public function up(): void
    {
        Schema::create('guia_semana_materiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_semana_id')->constrained('guia_semanas')->onDelete('cascade');
            $table->string('nombre');
            $table->longText('contenido_texto');
            // Huella del archivo: el mismo archivo no se adjunta dos veces a la misma semana
            $table->string('hash_archivo', 64);
            $table->timestamps();

            $table->unique(['guia_semana_id', 'hash_archivo']);
        });
    }

    /**
     * Quita la tabla.
     */
    public function down(): void
    {
        Schema::dropIfExists('guia_semana_materiales');
    }
};
