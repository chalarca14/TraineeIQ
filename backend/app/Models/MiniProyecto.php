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
        'dificultad',
        'contenido',
    ];

    public function guia()
    {
        return $this->belongsTo(Guia::class, 'guia_id');
    }

    public function tema()
    {
        return $this->belongsTo(GuiaTema::class, 'tema_id');
    }
}