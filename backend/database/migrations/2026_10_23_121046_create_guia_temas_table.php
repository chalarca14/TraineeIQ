<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guia_temas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_id')->constrained('guias')->onDelete('cascade');
            // Semana del tema; nullable para guias creadas antes de dividir por semanas
            $table->foreignId('guia_semana_id')->nullable()->constrained('guia_semanas')->onDelete('cascade');
            $table->string('nombre_tema');
            $table->text('descripcion');
            $table->integer('orden')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guia_temas');
    }
};