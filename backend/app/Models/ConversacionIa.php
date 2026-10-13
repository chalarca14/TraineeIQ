<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversacionIa extends Model
{
    use HasFactory;

    protected $table = 'conversaciones_ia';

    protected $fillable = [
        'estudiante_id',
        'guia_id',
        'tipo', // 'guiada' o 'libre'
        'titulo',
    ];

    //Una conversación pertenece a un estudiante (usuario).

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    //Una conversación puede estar enlazada opcionalmente a una guía.

    public function guia()
    {
        return $this->belongsTo(Guia::class, 'guia_id');
    }

    //Una conversación contiene muchos mensajes (intercambios de chat).

    public function mensajes()
    {
        return $this->hasMany(MensajeIa::class, 'conversacion_id');
    }
}