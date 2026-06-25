<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MensajeChat extends Model
{
    //Campos que se pueden llenar masivamente
    protected $fillable = [
        'recomendacion_id',
        'rol',
        'contenido',
    ];

    //Relacion: pertenece a una recomendacion
    public function recomendacion(){
        return $this->belongsTo(Recomendacion::class);
    }
}
