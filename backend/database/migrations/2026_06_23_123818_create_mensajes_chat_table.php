<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void{
        Schema::create('mensaje_chat', function (Blueprint $table){
            $table->id();
            $table->foreignId('recomendacion_id')->constrained('recomendaciones')->onDelete('cascade');
            $table->enum('rol', ['estudiante', 'ia']);
            $table->text('contenido');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes_chat');
    }
};
