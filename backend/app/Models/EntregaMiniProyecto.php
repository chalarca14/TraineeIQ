<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntregaMiniProyecto extends Model
{
    use HasFactory;

    protected $table = 'entregas_mini_proyecto';

    protected $fillable = [
        'mini_proyecto_id',
        'estudiante_id',
        'repo_url',
        'commit_sha',
        'estado', // 'pendiente', 'evaluando', 'evaluado' o 'error'
        'puntaje',
        'evaluacion',
        'intentos',
        'intentos_hoy',
        'fecha_intentos',
        'evaluado_en',
    ];

    /**
     * Convierte el JSON de la evaluación y las fechas a tipos de PHP.
     */
    protected function casts(): array
    {
        return [
            'evaluacion'     => 'array',
            'fecha_intentos' => 'date',
            'evaluado_en'    => 'datetime',
        ];
    }

    //Una entrega pertenece a un mini proyecto.

    public function miniProyecto()
    {
        return $this->belongsTo(MiniProyecto::class, 'mini_proyecto_id');
    }

    //Una entrega pertenece al estudiante que la hizo.

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }
}
