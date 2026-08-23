<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversaciones_ia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('guia_id')->nullable()->constrained('guias')->onDelete('set null');
            $table->enum('tipo', ['guiada', 'libre'])->default('guiada');
            $table->string('titulo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversaciones_ia');
    }
};