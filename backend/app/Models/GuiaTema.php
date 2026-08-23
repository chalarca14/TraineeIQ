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

    public function guia()
    {
        return $this->belongsTo(Guia::class, 'guia_id');
    }

    public function recomendaciones()
    {
        return $this->hasMany(Recomendacion::class, 'tema_id');
    }
}