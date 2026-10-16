<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guia extends Model
{
    use HasFactory;

    protected $table = 'guias';

    protected $fillable = [
        'nombre',
        'archivo_pdf',
        'semanas_totales',
        'grupo_id',
        'usuario_id',
        'origen', // 'grupo' o 'personal'
    ];

     // Una guía puede pertenecer a un grupo (si es de origen grupal).

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    //Una guía puede pertenecer a un usuario (si es de origen personal).

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    //Una guía se divide en varios temas o módulos.
    
    public function temas()
    {
        return $this->hasMany(GuiaTema::class, 'guia_id');
    }

    //Una guía tiene muchas recomendaciones generadas por la IA.
    
    public function recomendaciones()
    {
        return $this->hasMany(Recomendacion::class, 'guia_id');
    }

    //Una guía tiene varios mini proyectos sugeridos.

    public function miniProyectos()
    {
        return $this->hasMany(MiniProyecto::class, 'guia_id');
    }
}