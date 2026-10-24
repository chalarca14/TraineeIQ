<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Las guias personales no pertenecen a ningun grupo, asi que grupo_id debe aceptar null
     * (igual que usuario_id es null en las guias de grupo).
     */
    public function up(): void
    {
        Schema::table('guias', function (Blueprint $table) {
            $table->unsignedBigInteger('grupo_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     * Falla si ya existen guias personales con grupo_id null.
     */
    public function down(): void
    {
        Schema::table('guias', function (Blueprint $table) {
            $table->unsignedBigInteger('grupo_id')->nullable(false)->change();
        });
    }
};
