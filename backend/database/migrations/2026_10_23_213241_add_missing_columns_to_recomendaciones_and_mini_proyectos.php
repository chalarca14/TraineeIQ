<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega las columnas que ya usan RecomendacionController, MiniProyectoController
     * y sus modelos, pero que no estaban en las migraciones originales.
     */
    public function up(): void
    {
        // Cada recomendacion se genera para un tema puntual de la guia
        Schema::table('recomendaciones', function (Blueprint $table) {
            $table->foreignId('tema_id')->nullable()->after('guia_id')
                ->constrained('guia_temas')->onDelete('cascade');
        });

        // Un mini proyecto siempre pertenece a una guia (el tema es opcional)
        // y guarda en contenido las instrucciones completas paso a paso
        Schema::table('mini_proyectos', function (Blueprint $table) {
            $table->foreignId('guia_id')->after('id')
                ->constrained('guias')->onDelete('cascade');
            $table->longText('contenido')->nullable()->after('dificultad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recomendaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tema_id');
        });

        Schema::table('mini_proyectos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guia_id');
            $table->dropColumn('contenido');
        });
    }
};
