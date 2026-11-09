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

    // Ocultos por defecto: el texto es pesado y solo lo muestran el detalle de la semana
    // y la guia completa, despues de revisar que la semana este liberada. El hash nunca sale.
    protected $hidden = [
        'contenido_texto',
        'hash_contenido',
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

    //Entregas de los mini proyectos de la semana; con ellas se suma la experiencia.

    public function entregas()
    {
        return $this->hasManyThrough(EntregaMiniProyecto::class, MiniProyecto::class, 'guia_semana_id', 'mini_proyecto_id');
    }
}
