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
        'guia_semana_id',
        'nombre_tema',
        'descripcion',
        'orden',
    ];

    //Un tema pertenece a una guía específica.

    public function guia()
    {
        return $this->belongsTo(Guia::class, 'guia_id');
    }

    //Un tema pertenece a una semana de la guía.

    public function semana()
    {
        return $this->belongsTo(GuiaSemana::class, 'guia_semana_id');
    }
}
