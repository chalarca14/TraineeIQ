<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstudianteGrupo extends Model
{
    //Campos que se pueden llenar masivamente

    protected $fillable = [
        'estudiante_id',
        'grupo_id',
        'semana_actual',
    ];

    //Relacion:Pertenece a un estudiante(usuario)

    public function estuddiante(){
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    //Relacion: Pertenece a un grupo

    public function grupo(){
        return $this->belongsTo(Grupo::class);
    }
}
