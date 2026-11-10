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
        'guia_semana_id',
        'orden',
        'titulo',
        'descripcion',
        'dificultad', // 'basico', 'intermedio', 'avanzado'
        'contenido',
        'requisitos_funcionales',
        'buenas_practicas',
        'criterios_evaluacion',
    ];

    /**
     * Convierte las columnas JSON a arreglos de PHP.
     */
    protected function casts(): array
    {
        return [
            'requisitos_funcionales' => 'array',
            'buenas_practicas'       => 'array',
            'criterios_evaluacion'   => 'array',
        ];
    }

    //Un mini proyecto pertenece a una guía.

    public function guia()
    {
        return $this->belongsTo(Guia::class, 'guia_id');
    }

    //Un mini proyecto pertenece a una semana de la guía.

    public function semana()
    {
        return $this->belongsTo(GuiaSemana::class, 'guia_semana_id');
    }

    //Un mini proyecto tiene una entrega por cada estudiante que lo trabaja.

    public function entregas()
    {
        return $this->hasMany(EntregaMiniProyecto::class, 'mini_proyecto_id');
    }
}
