<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'instructor_id',
        'codigo_acceso',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function estudiantes()
    {
        return $this->belongsToMany(User::class, 'estudiante_grupo', 'grupo_id', 'estudiante_id')
                    ->withPivot('semana_actual')
                    ->withTimestamps();
    }

    public function guias()
    {
        return $this->hasMany(Guia::class, 'grupo_id');
    }
}