<?php

namespace App\Services;

use App\Models\ConversacionIa;
use App\Models\Grupo;
use App\Models\Guia;
use App\Models\Notificacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Borra guías (una sola, o todas las de un grupo al borrarlo) sin dejar datos sueltos.
 *
 * La base de datos ya borra en cascada al borrar la guía: semanas, temas, recomendaciones,
 * mini proyectos, entregas con su evaluación y los avisos de evaluación (llave a la entrega).
 * Dos cosas NO se borran solas y por eso se borran aquí:
 * - los chats guiados de la guía: su llave queda en null (onDelete set null) y el chat quedaría
 *   sin guía; se borran, y sus mensajes caen en cascada;
 * - los avisos de "guía nueva" y "semana liberada": guardan la guía dentro de `datos` (JSON),
 *   sin llave foránea.
 * El archivo (PDF o Excel) se borra del disco al final, cuando la base ya quedó bien.
 */
class EliminarGuiaService
{
    /**
     * Borra una guía con todo lo suyo.
     */
    public function eliminarGuia(Guia $guia): void
    {
        $archivos = array_filter([$guia->archivo_pdf]);

        DB::transaction(function () use ($guia) {
            $this->borrarLoQueNoCaeEnCascada([$guia->id]);
            $guia->delete();
        });

        $this->borrarArchivos($archivos);
    }

    /**
     * Borra un grupo con todas sus guías y todo lo de ellas. Las inscripciones caen en cascada;
     * las cuentas de los estudiantes no se tocan.
     */
    public function eliminarGrupo(Grupo $grupo): void
    {
        $guias = $grupo->guias()->get(['id', 'archivo_pdf']);
        $archivos = $guias->pluck('archivo_pdf')->filter()->values()->all();

        DB::transaction(function () use ($grupo, $guias) {
            $this->borrarLoQueNoCaeEnCascada($guias->pluck('id')->all());
            // Borrar el grupo borra en cascada sus guías y, con ellas, todo lo demás
            $grupo->delete();
        });

        $this->borrarArchivos($archivos);
    }

    /**
     * Chats guiados y avisos de estas guías: lo único que la base no borra sola.
     *
     * @param  list<int>  $guiaIds
     */
    private function borrarLoQueNoCaeEnCascada(array $guiaIds): void
    {
        if ($guiaIds === []) {
            return;
        }

        ConversacionIa::whereIn('guia_id', $guiaIds)->delete();
        Notificacion::whereIn('datos->guia_id', $guiaIds)->delete();
    }

    /**
     * Borra los archivos del disco. Si falla, la guía ya está borrada: un archivo que quede
     * no lo ve nadie, así que solo se anota en el log.
     *
     * @param  list<string>  $rutas
     */
    private function borrarArchivos(array $rutas): void
    {
        if ($rutas === []) {
            return;
        }

        try {
            Storage::disk('public')->delete($rutas);
        } catch (\Throwable $e) {
            Log::warning('No se pudieron borrar los archivos de la guía', ['mensaje' => $e->getMessage()]);
        }
    }
}
