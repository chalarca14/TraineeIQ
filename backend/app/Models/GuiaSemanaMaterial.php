<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Guía detallada que el instructor adjunta a una semana (por ejemplo, la guía de la semana 3
 * que entrega a sus aprendices). Solo se guarda su texto.
 */
class GuiaSemanaMaterial extends Model
{
    protected $table = 'guia_semana_materiales';

    protected $fillable = [
        'guia_semana_id',
        'nombre',
        'contenido_texto',
        'hash_archivo',
    ];

    // La huella solo sirve para no repetir archivos: nunca sale en las respuestas
    protected $hidden = [
        'hash_archivo',
    ];

    /**
     * La semana a la que pertenece.
     */
    public function semana()
    {
        return $this->belongsTo(GuiaSemana::class, 'guia_semana_id');
    }
}
