<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuiaSemana extends Model
{
    use HasFactory;

    protected $table = 'guia_semanas';

    protected $fillable = [
        'guia_id',
        'numero_semana',
        'titulo',
        'resumen',
        'contenido_texto',
        'hash_contenido',
        'estado_generacion', // 'pendiente', 'generando', 'listo' o 'error'
        'error_mensaje',
    ];

    //Una semana pertenece a una guía.

    public function guia()
    {
        return $this->belongsTo(Guia::class, 'guia_id');
    }

    //Una semana agrupa varios temas de la guía.

    public function temas()
    {
        return $this->hasMany(GuiaTema::class, 'guia_semana_id')->orderBy('orden');
    }

    //Una semana tiene su juego de recomendaciones, compartido por todo el grupo.

    public function recomendaciones()
    {
        return $this->hasMany(Recomendacion::class, 'guia_semana_id')->orderBy('orden');
    }

    //Una semana tiene sus mini proyectos, compartidos por todo el grupo.

    public function miniProyectos()
    {
        return $this->hasMany(MiniProyecto::class, 'guia_semana_id')->orderBy('orden');
    }
}
