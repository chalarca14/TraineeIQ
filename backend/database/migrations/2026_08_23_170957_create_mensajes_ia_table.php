<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes_ia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('conversaciones_ia')->onDelete('cascade');
            $table->enum('emisor', ['usuario', 'ia'])->default('usuario');
            $table->text('contenido');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes_ia');
    }
};