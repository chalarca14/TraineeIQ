<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'modo_preferido',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function gruposComoInstructor()
    {
        return $this->hasMany(Grupo::class, 'instructor_id');
    }

    public function gruposComoEstudiante()
    {
        return $this->belongsToMany(Grupo::class, 'estudiante_grupo', 'estudiante_id', 'grupo_id')
                    ->withPivot('semana_actual')
                    ->withTimestamps();
    }

    public function guiasPersonales()
    {
        return $this->hasMany(Guia::class, 'usuario_id');
    }

    public function conversacionesIa()
    {
        return $this->hasMany(ConversacionIa::class, 'estudiante_id');
    }
}