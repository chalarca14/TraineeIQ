<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory;

    protected $table = 'grupos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'instructor_id',
        'codigo_acceso',
        'semana_actual',
    ];

    //Un grupo pertenece a un instructor (usuario).

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    //Relación muchos a muchos con estudiantes a través de la tabla pivote 'estudiante_grupo'.

    public function estudiantes()
    {
        return $this->belongsToMany(User::class, 'estudiante_grupo', 'grupo_id', 'estudiante_id')
                    ->withTimestamps();
    }

    //Un grupo tiene asociadas varias guías de estudio grupales.

    public function guias()
    {
        return $this->hasMany(Guia::class, 'grupo_id');
    }

    //Mini proyectos de todas las guías del grupo, pasando por la tabla guias.

    public function miniProyectos()
    {
        return $this->hasManyThrough(MiniProyecto::class, Guia::class, 'grupo_id', 'guia_id');
    }
}