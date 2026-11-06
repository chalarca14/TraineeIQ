<?php

namespace App\Policies;

use App\Models\MiniProyecto;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MiniProyectoPolicy
{
    /**
     * Un mini proyecto se ve bajo las mismas reglas que la semana a la que pertenece.
     */
    public function view(User $user, MiniProyecto $miniProyecto): Response
    {
        return (new GuiaPolicy())->viewSemana($user, $miniProyecto->guia, $miniProyecto->semana->numero_semana);
    }
}
