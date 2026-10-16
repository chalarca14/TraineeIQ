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
        'nivel', // 'basico', 'recomendado', 'importante', 'avanzado'
        'contenido',
    ];

    //Una recomendación va dirigida a un estudiante específico.

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    //Pertenece a una guía de estudio.

    public function guia()
    {
        return $this->belongsTo(Guia::class, 'guia_id');
    }

    //Opcionalmente puede estar vinculada a un tema específico.

    public function tema()
    {
        return $this->belongsTo(GuiaTema::class, 'tema_id');
    }
}