<?php

namespace App\Traits;

use App\Models\Guia;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Trait reutilizable: verifica que el estudiante autenticado tiene permiso
 * sobre una guia (personal propia, o de un grupo en el que esta inscrito).
 * Usado por RecomendacionController, MiniProyectoController y, mas adelante,
 * por las estrategias del chat (modo guiado).
 */
trait VerificaAccesoAGuia
{
    protected function verificarAccesoAGuia(Guia $guia): void
    {
        $esGuiaPersonalDelEstudiante = $guia->usuario_id === auth()->id();

        $esGuiaDeUnGrupoDelEstudiante = $guia->grupo_id !== null
            && auth()->user()->grupos()->where('grupos.id', $guia->grupo_id)->exists();

        if (!$esGuiaPersonalDelEstudiante && !$esGuiaDeUnGrupoDelEstudiante) {
            throw new AuthorizationException('No tienes acceso a esta guia.');
        }
    }
}