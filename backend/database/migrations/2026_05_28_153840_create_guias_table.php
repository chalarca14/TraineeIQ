<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\table;

return new class extends Migration
{
    /**
     * Tabla donde se guarfan los PDFs subidor por los istruuctores
     */
    public function up(): void
    {
        Schema::create('guias', function (Blueprint $table) {
            //Llave primeria auto incremental
            $table->id();

            //Nombre de la guia
            $table->string('nombre');

            //Llave foranea del grupo al que pertenece a la que pertenece la guia
            $table->foreignId('grupo_id')->constrained('grupos')->onDelete('cascade');

            //Ruta del archivo PDF guardado en el servidor
            $table->string('archivo_pdf');

            //Numero total de semanas que tiene la guia
            $table->integer('semanas_totales');

            //Fechas de creacion y actualizacion automaticas
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla guia si se revierte la migracion
     */
    public function down(): void
    {
        Schema::dropIfExists('guias');
    }
};
