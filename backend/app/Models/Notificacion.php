<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_id',
        'entrega_mini_proyecto_id',
        'tipo', // 'evaluacion_entrega'
        'mensaje',
        'datos',
        'leida_en',
    ];

    protected $casts = [
        'datos'    => 'array',
        'leida_en' => 'datetime',
    ];

    //Una notificacion pertenece al usuario que la recibe.

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    //Una notificacion de evaluacion apunta a la entrega que la origino.

    public function entrega()
    {
        return $this->belongsTo(EntregaMiniProyecto::class, 'entrega_mini_proyecto_id');
    }
}
