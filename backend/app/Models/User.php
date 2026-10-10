<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Asegúrate de mantener esto si usas tokens de API (Sanctum)

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // RELACIONES DE TRAINEEIQ (Documento Maestro v3)

    /**
     * Grupos en los que el usuario actúa como instructor.
     */
    public function gruposComoInstructor()
    {
        return $this->hasMany(Grupo::class, 'instructor_id');
    }

    /**
     * Grupos a los que el usuario está inscrito como estudiante.
     */
    public function gruposInscritos()
    {
        return $this->belongsToMany(Grupo::class, 'estudiante_grupo', 'estudiante_id', 'grupo_id')
                    ->using(EstudianteGrupo::class)
                    ->withPivot('semana_actual')
                    ->withTimestamps();
    }

    /**
     * Guías de estudio personales creadas por el usuario.
     */
    public function guiasPersonales()
    {
        return $this->hasMany(Guia::class, 'usuario_id');
    }

    /**
     * Conversaciones con la IA iniciadas por el estudiante.
     */
    public function conversacionesIa()
    {
        return $this->hasMany(ConversacionIa::class, 'estudiante_id');
    }

    /**
     * Recomendaciones emitidas para el estudiante.
     */
    public function recomendaciones()
    {
        return $this->hasMany(Recomendacion::class, 'estudiante_id');
    }
}