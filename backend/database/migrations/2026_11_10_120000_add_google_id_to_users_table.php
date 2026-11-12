<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega google_id a users para vincular la cuenta con "Continuar con Google".
     * Es una migración nueva porque producción ya tiene la tabla users con datos.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identificador fijo de la cuenta de Google ("sub" del token); null si nunca entró con Google.
            // unique() también crea el índice que usa la búsqueda al iniciar sesión.
            $table->string('google_id')->nullable()->unique()->after('email');
        });
    }

    /**
     * Revierte la migración quitando la columna google_id.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
        });
    }
};
