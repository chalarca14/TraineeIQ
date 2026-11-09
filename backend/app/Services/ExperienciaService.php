<?php

namespace App\Services;

use App\Models\EntregaMiniProyecto;
use App\Models\Guia;
use App\Models\GuiaSemana;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Experiencia (XP) del Modo Personal. El XP de un mini proyecto es el mejor puntaje
 * de su entrega, y la semana siguiente se abre al llegar a un porcentaje del XP de la
 * semana. No se guarda aparte: se suma desde las entregas, asi que nunca se desincroniza.
 * En Modo Grupo no se usa: ahi las semanas las libera el instructor.
 */
class ExperienciaService
{
    // Un mini proyecto da como maximo 100 XP, porque su puntaje va de 0 a 100
    private const XP_POR_MINI_PROYECTO = 100;

    /**
     * XP de cada semana de una guia personal, indexado por numero de semana:
     * xp_obtenido (suma de mejores puntajes del dueño), xp_maximo y xp_requerido.
     * Hace una sola consulta para todas las semanas.
     */
    public function progresoSemanas(Guia $guia): Collection
    {
        $porcentaje = (int) config('services.experiencia.porcentaje_desbloqueo');

        return $guia->semanas()
            ->select(['id', 'guia_id', 'numero_semana'])
            ->withCount('miniProyectos')
            ->withSum(
                ['entregas as xp_obtenido' => fn ($entregas) => $entregas->where('entregas_mini_proyecto.estudiante_id', $guia->usuario_id)],
                'mejor_puntaje'
            )
            ->get()
            ->mapWithKeys(function (GuiaSemana $semana) use ($porcentaje) {
                $maximo = $semana->mini_proyectos_count * self::XP_POR_MINI_PROYECTO;

                return [$semana->numero_semana => [
                    'xp_obtenido'  => (int) $semana->xp_obtenido,
                    'xp_maximo'    => $maximo,
                    'xp_requerido' => (int) ceil($maximo * $porcentaje / 100),
                ]];
            });
    }

    /**
     * Ultima semana que el dueño puede abrir. La semana 1 siempre esta abierta, y se
     * avanza mientras cada semana alcance su XP requerido. Una semana sin mini
     * proyectos no se puede completar, asi que detiene el avance.
     */
    public function ultimaSemanaLiberada(Collection $progreso): int
    {
        $ultima = 1;

        foreach ($progreso as $numero => $xp) {
            if ($xp['xp_maximo'] === 0 || $xp['xp_obtenido'] < $xp['xp_requerido']) {
                break;
            }

            $ultima = $numero + 1;
        }

        return $ultima;
    }

    /**
     * XP total del estudiante: suma de sus mejores puntajes en sus guias personales.
     */
    public function xpTotal(User $estudiante): int
    {
        return (int) EntregaMiniProyecto::where('estudiante_id', $estudiante->id)
            ->whereHas('miniProyecto.guia', fn ($guias) => $guias
                ->where('origen', 'personal')
                ->where('usuario_id', $estudiante->id))
            ->sum('mejor_puntaje');
    }
}
