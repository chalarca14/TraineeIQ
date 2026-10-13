<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniProyecto extends Model
{
    use HasFactory;

    protected $table = 'mini_proyectos';

    protected $fillable = [
        'guia_id',
        'tema_id',
        'titulo',
        'descripcion',
        'dificultad', // 'basico', 'intermedio', 'avanzado'
        'contenido',
    ];

    //Un mini proyecto pertenece a una guía.

    public function guia()
    {
        return $this->belongsTo(Guia::class, 'guia_id');
    }

    // mini proyecto puede pertenecer opcionalmente a un tema específico.

    public function tema()
    {
        return $this->belongsTo(GuiaTema::class, 'tema_id');
    }
}