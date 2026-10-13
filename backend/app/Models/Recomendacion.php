<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recomendacion extends Model
{
    //Campos que se pueden llenar masivamente

    protected $fillable = [
        'estudiante_id',
        'guia_id',
        'semana',
        'contenido',
    ];

    //Convierte el campo contenido de JSON a array automaticamente

    protected $casts = [
        'contenido' => 'array',
    ];

    //Relacion: pertenece a un estudiante (usuario)

    public function estudiante(){
        return $this->belongsTo(User:: class, 'estudiante_id');
    }

    //Relacion: pertenece a una guia

    public function guia(){
        return $this->belongsTo(Guia::class);
    }
}
