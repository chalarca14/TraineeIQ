<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MensajeIa extends Model
{
    use HasFactory;

    protected $table = 'mensajes_ia';

    protected $fillable = [
        'conversacion_id',
        'emisor',
        'contenido',
    ];

    public function conversacion()
    {
        return $this->belongsTo(ConversacionIa::class, 'conversacion_id');
    }
}