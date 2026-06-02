<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    // campos que se pueden llenar masivamente

    protected $fillable = [
        'nombre',
        'descripcion',
        'instructor_id',
        'codido_acceso',
    ];

    //Relacion: un grupo pertenece a un instructor (Usuario)

    public function instructor(){
        return $this->belongsTo(User::class, 'instructor_id');
    }

    //Relacion: Un grupo tiene muchas guias

    public function guias(){
        return $this->hasMany(Guia::class);
    }

    //Relacion: un grupo tiene muchos estudiantes

    public function estudiantes(){
        return $this->hasMany(EstudianteGrupo::class);
    }
}
