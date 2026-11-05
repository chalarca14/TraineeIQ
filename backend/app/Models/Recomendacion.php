<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recomendacion extends Model
{
    use HasFactory;

    protected $table = 'recomendaciones';

    protected $fillable = [
        'guia_id',
        'guia_semana_id',
        'orden',
        'nivel', // 'basico', 'recomendado', 'importante', 'avanzado'
        'contenido',
    ];

    /**
     * Convierte el JSON de contenido (titulo y descripcion) a un arreglo de PHP.
     */
    protected function casts(): array
    {
        return [
            'contenido' => 'array',
        ];
    }

    //Pertenece a una guía de estudio.

    public function guia()
    {
        return $this->belongsTo(Guia::class, 'guia_id');
    }

    //Pertenece a una semana de la guía.

    public function semana()
    {
        return $this->belongsTo(GuiaSemana::class, 'guia_semana_id');
    }
}
