<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->onDelete('cascade');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('origen', ['grupo', 'personal'])->default('grupo');
            $table->string('archivo_pdf')->nullable();
            $table->integer('semanas_totales')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guias');
    }
};