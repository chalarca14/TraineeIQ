<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuiaTema extends Model
{
    use HasFactory;

    protected $table = 'guia_temas';

    protected $fillable = [
        'guia_id',
        'nombre_tema',
        'descripcion',
        'orden',
    ];

    //Un tema pertenece a una guía específica.

    public function guia()
    {
        return $this->belongsTo(Guia::class, 'guia_id');
    }

    //Un tema tiene muchas recomendaciones de IA asociadas.

    public function recomendaciones()
    {
        return $this->hasMany(Recomendacion::class, 'tema_id');
    }

    //Un tema tiene varios mini proyectos sugeridos.

    public function miniProyectos()
    {
        return $this->hasMany(MiniProyecto::class, 'tema_id');
    }
}