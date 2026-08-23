<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guia extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'grupo_id',
        'usuario_id',
        'origen',
        'archivo_pdf',
        'semanas_totales',
    ];

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function temas()
    {
        return $this->hasMany(GuiaTema::class, 'guia_id')->orderBy('orden');
    }

    public function miniProyectos()
    {
        return $this->hasMany(MiniProyecto::class, 'guia_id');
    }
}