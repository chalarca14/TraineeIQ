<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guia extends Model
{
    //Campos que se pueden llenar masivamente

    protected $fillable = [
        'nombre',
        'grupo_id',
        'archivo_pdf',
        'semanas_totales,'
    ];

    //Relacion: una guia pertenece a un grupo

    public function grupo(){
        return $this->belongsTo(Grupo::class);
    }

    //Relacion: Una guia tiene muchas recomendaciones

    public function recomendaciones(){
        return $this->hasMany(Recomendacion::class);
    }
}
