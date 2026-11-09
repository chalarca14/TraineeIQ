<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla notificaciones: avisos que el usuario ve en la campanita.
     * Es una migracion nueva porque la base de produccion ya tiene las demas aplicadas.
     */
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            //Llave primaria autoincremental
            $table->id();
            //Usuario que recibe el aviso (hoy, el instructor del grupo)
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            //Entrega que origino el aviso; si se borra la entrega, se borra el aviso
            $table->foreignId('entrega_mini_proyecto_id')->nullable()
                ->constrained('entregas_mini_proyecto')->onDelete('cascade');
            //Clase de aviso, para poder agregar otros mas adelante
            $table->string('tipo', 40);
            //Texto ya armado que muestra la campanita
            $table->string('mensaje', 500);
            //Datos sueltos para que el frontend arme enlaces o filtros
            $table->json('datos')->nullable();
            //Cuando el usuario lo marco como leido; nulo si aun no lo lee
            $table->timestamp('leida_en')->nullable();
            //Fechas de creacion y actualizacion automaticas
            $table->timestamps();

            //La campanita siempre busca los avisos no leidos de un usuario
            $table->index(['usuario_id', 'leida_en']);
        });
    }

    /**
     * Elimina la tabla notificaciones si se revierte la migracion
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
