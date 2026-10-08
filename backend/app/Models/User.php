<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /**
     * HasApiTokens:
     * Permite generar tokens con Laravel Sanctum.
     */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Campos permitidos para asignación masiva.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'modo_preferido',
    ];

    /**
     * Campos ocultos en respuestas JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversión automática de tipos.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
