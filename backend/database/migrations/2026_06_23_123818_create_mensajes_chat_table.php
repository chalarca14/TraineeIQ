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
            // Clave foránea que conecta directamente con la tabla conversaciones_ia
            $table->foreignId('conversacion_id')->constrained('conversaciones_ia')->onDelete('cascade');
            
            // Emisor de la interacción (coincide con tu frontend y lógica de servicio)
            $table->enum('emisor', ['usuario', 'ia']);
            
            $table->text('contenido');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes_chat');
    }
};