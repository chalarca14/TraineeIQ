<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna rol a la tabla users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', [
                'instructor',
                'estudiante',
            ])->default('estudiante')->after('email');

            $table->enum('modo_preferido', [
                'grupo',
                'personal',
            ])->nullable()->after('rol')
                ->comment('Preferencia de interfaz del estudiante; no representa un rol ni un permiso.');
        });
    }

    /**
     * Revierte la migración eliminando la columna rol.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rol', 'modo_preferido']);
        });
    }
};
