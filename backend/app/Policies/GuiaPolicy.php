<?php

namespace App\Policies;

use App\Models\Guia;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GuiaPolicy
{
    /**
     * Una guia personal solo la ve su dueño, ni siquiera un instructor.
     * Una de grupo la ven su instructor y los estudiantes inscritos.
     */
    public function view(User $user, Guia $guia): Response
    {
        $puedeVer = $guia->origen === 'personal'
            ? $guia->usuario_id === $user->id
            : $guia->grupo?->instructor_id === $user->id
                || $user->gruposInscritos()->whereKey($guia->grupo_id)->exists();

        return $puedeVer ? Response::allow() : Response::deny('No tienes acceso a esta guía.');
    }

    /**
     * Permite leer el contenido de una semana a quien ve la guia, siempre que
     * esa semana ya este liberada para el.
     */
    public function viewSemana(User $user, Guia $guia, int $numeroSemana): Response
    {
        $acceso = $this->view($user, $guia);

        if ($acceso->denied()) {
            return $acceso;
        }

        return $guia->semanaLiberadaPara($user, $numeroSemana)
            ? Response::allow()
            : Response::deny('Esta semana aún no está disponible.');
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
