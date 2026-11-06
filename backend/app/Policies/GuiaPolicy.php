<?php

namespace App\Policies;

use App\Models\Guia;
use App\Models\User;

class GuiaPolicy
{
    /**
     * Una guia personal solo la ve su dueño, ni siquiera un instructor.
     * Una de grupo la ven su instructor y los estudiantes inscritos.
     */
    public function view(User $user, Guia $guia): bool
    {
        if ($guia->origen === 'personal') {
            return $guia->usuario_id === $user->id;
        }

        return $guia->grupo?->instructor_id === $user->id
            || $user->gruposInscritos()->whereKey($guia->grupo_id)->exists();
    }

    /**
     * Solo el dueño cambia una guia: el estudiante en las personales
     * y el instructor del grupo en las de grupo.
     */
    public function update(User $user, Guia $guia): bool
    {
        if ($guia->origen === 'personal') {
            return $guia->usuario_id === $user->id;
        }

        return $guia->grupo?->instructor_id === $user->id;
    }
}
