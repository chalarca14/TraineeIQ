<?php

namespace App\Policies;

use App\Models\Grupo;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GrupoPolicy
{
    /**
     * Un grupo solo lo ven su instructor y los estudiantes inscritos.
     */
    public function view(User $user, Grupo $grupo): Response
    {
        $puedeVer = $grupo->instructor_id === $user->id
            || $user->gruposInscritos()->whereKey($grupo->id)->exists();

        return $puedeVer ? Response::allow() : Response::deny('No tienes acceso a este grupo.');
    }

    /**
     * Solo el instructor dueño cambia los datos de su grupo.
     */
    public function update(User $user, Grupo $grupo): bool
    {
        return $grupo->instructor_id === $user->id;
    }
}
