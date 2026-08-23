<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mini_proyectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_id')->constrained('guias')->onDelete('cascade');
            $table->foreignId('tema_id')->constrained('guia_temas')->onDelete('cascade');
            $table->string('titulo');
            $table->text('descripcion');
            $table->enum('dificultad', ['facil', 'medio', 'dificil'])->default('facil');
            $table->text('contenido')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mini_proyectos');
    }
};