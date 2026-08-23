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
        'tipo',
        'titulo',
    ];

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function guia()
    {
        return $this->belongsTo(Guia::class, 'guia_id');
    }

    public function mensajes()
    {
        return $this->hasMany(MensajeIa::class, 'conversacion_id');
    }
}