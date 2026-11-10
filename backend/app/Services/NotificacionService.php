<?php

namespace App\Services;

use App\Models\EntregaMiniProyecto;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Crea y administra los avisos de la campanita. No hay tiempo real: el frontend
 * consulta la lista cada cierto tiempo, porque la app no usa colas ni workers.
 */
class NotificacionService
{
    private const TIPO_EVALUACION = 'evaluacion_entrega';

    private const POR_PAGINA = 20;

    // Como se muestra cada dificultad en el mensaje
    private const DIFICULTADES = ['basico' => 'básico', 'intermedio' => 'intermedio', 'avanzado' => 'avanzado'];

    /**
     * Avisa al instructor del grupo que un estudiante evaluo su mini proyecto.
     * En Modo Personal no hace nada: el instructor nunca ve esas guias.
     * Si el instructor aun no leyo el aviso anterior de la misma entrega, lo actualiza
     * en lugar de crear otro, para no llenarle la campanita con reevaluaciones.
     */
    public function avisarEvaluacion(EntregaMiniProyecto $entrega): void
    {
        $entrega->loadMissing(['estudiante', 'miniProyecto.semana.guia.grupo']);

        $proyecto = $entrega->miniProyecto;
        $guia = $proyecto->semana->guia;

        if ($guia->origen !== 'grupo' || $guia->grupo === null) {
            return;
        }

        $datos = [
            'estudiante_id'    => $entrega->estudiante_id,
            'estudiante'       => $entrega->estudiante->name,
            'mini_proyecto_id' => $proyecto->id,
            'mini_proyecto'    => $proyecto->titulo,
            'dificultad'       => $proyecto->dificultad,
            'guia_id'          => $guia->id,
            'guia'             => $guia->nombre,
            'grupo_id'         => $guia->grupo->id,
            'grupo'            => $guia->grupo->nombre,
            'numero_semana'    => $proyecto->semana->numero_semana,
            'puntaje'          => $entrega->puntaje,
            'intento'          => $entrega->intentos,
        ];

        $noLeida = Notificacion::where([
            'usuario_id'               => $guia->grupo->instructor_id,
            'entrega_mini_proyecto_id' => $entrega->id,
            'tipo'                     => self::TIPO_EVALUACION,
        ])->whereNull('leida_en')->first();

        $campos = ['mensaje' => $this->mensajeEvaluacion($datos), 'datos' => $datos];

        if ($noLeida !== null) {
            // touch() renueva la fecha aunque el texto no cambie, para que suba en la lista
            $noLeida->fill($campos)->touch();

            return;
        }

        Notificacion::create($campos + [
            'usuario_id'               => $guia->grupo->instructor_id,
            'entrega_mini_proyecto_id' => $entrega->id,
            'tipo'                     => self::TIPO_EVALUACION,
        ]);
    }

    /**
     * Devuelve una pagina de avisos del usuario, del mas reciente al mas viejo.
     */
    public function listar(User $usuario): LengthAwarePaginator
    {
        return Notificacion::where('usuario_id', $usuario->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA);
    }

    /**
     * Cuenta los avisos que el usuario aun no ha leido: es el numero de la campanita.
     */
    public function contarNoLeidas(User $usuario): int
    {
        return Notificacion::where('usuario_id', $usuario->id)->whereNull('leida_en')->count();
    }

    /**
     * Marca un aviso como leido. Si ya lo estaba, conserva la fecha original.
     */
    public function marcarLeida(Notificacion $notificacion): Notificacion
    {
        if ($notificacion->leida_en === null) {
            $notificacion->update(['leida_en' => now()]);
        }

        return $notificacion;
    }

    /**
     * Marca como leidos todos los avisos pendientes del usuario y dice cuantos eran.
     */
    public function marcarTodas(User $usuario): int
    {
        return Notificacion::where('usuario_id', $usuario->id)
            ->whereNull('leida_en')
            ->update(['leida_en' => now()]);
    }

    /**
     * Arma el texto del aviso. La primera evaluacion dice "termino"; las siguientes, "volvio a evaluar".
     */
    private function mensajeEvaluacion(array $datos): string
    {
        $proyecto = sprintf(
            '«%s» (%s, semana %d de «%s»)',
            $datos['mini_proyecto'],
            self::DIFICULTADES[$datos['dificultad']] ?? $datos['dificultad'],
            $datos['numero_semana'],
            $datos['guia']
        );

        if ($datos['intento'] <= 1) {
            return "{$datos['estudiante']} terminó el mini proyecto {$proyecto} con {$datos['puntaje']}/100.";
        }

        return "{$datos['estudiante']} volvió a evaluar el mini proyecto {$proyecto} "
            . "y obtuvo {$datos['puntaje']}/100 (intento {$datos['intento']}).";
    }
}
