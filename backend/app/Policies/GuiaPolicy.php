<?php

namespace App\Policies;

use App\Models\Guia;
use App\Models\User;

class GuiaPolicy
{
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
