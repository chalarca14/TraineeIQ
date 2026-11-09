<?php

namespace App\Policies;

use App\Models\Notificacion;
use App\Models\User;

class NotificacionPolicy
{
    /**
     * Solo quien recibio el aviso puede marcarlo como leido.
     */
    public function update(User $user, Notificacion $notificacion): bool
    {
        return $notificacion->usuario_id === $user->id;
    }
}
