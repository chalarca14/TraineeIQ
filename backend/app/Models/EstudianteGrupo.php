<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EstudianteGrupo extends Pivot
{
    protected $table = 'estudiante_grupo';

    protected $fillable = [
        'estudiante_id',
        'grupo_id',
        'semana_actual',
    ];

    //Pertenece a un estudiante (usuario).

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    //Pertenece a un grupo.

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }
}