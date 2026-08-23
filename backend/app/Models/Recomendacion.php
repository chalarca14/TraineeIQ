<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recomendacion extends Model
{
    use HasFactory;

    protected $table = 'recomendaciones';

    protected $fillable = [
        'estudiante_id',
        'guia_id',
        'tema_id',
        'semana',
        'nivel',
        'contenido',
    ];

    protected $casts = [
        'contenido' => 'array',
    ];

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function guia()
    {
        return $this->belongsTo(Guia::class, 'guia_id');
    }

    public function tema()
    {
        return $this->belongsTo(GuiaTema::class, 'tema_id');
    }
}