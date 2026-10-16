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
                    ->withPivot('semana_actual')
                    ->withTimestamps();
    }

    //Un grupo tiene asociadas varias guías de estudio grupales.

    public function guias()
    {
        return $this->hasMany(Guia::class, 'grupo_id');
    }
}