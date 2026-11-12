<?php

namespace App\Services;

use App\Models\EntregaMiniProyecto;
use App\Models\Grupo;
use App\Models\Guia;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Crea y administra los avisos de la campanita. No hay tiempo real: el frontend
 * consulta la lista cada cierto tiempo, porque la app no usa colas ni workers.
 */
class NotificacionService
{
    private const TIPO_EVALUACION = 'evaluacion_entrega';

    // Avisos para los estudiantes del grupo
    private const TIPO_GUIA_NUEVA = 'guia_nueva';
    private const TIPO_SEMANA_LIBERADA = 'semana_liberada';

    // Largo máximo de cada nombre dentro del mensaje: la columna mensaje admite 500 caracteres
    private const LARGO_NOMBRE = 120;

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
     * Avisa a cada estudiante del grupo que el instructor subió una guía nueva.
     * Las guías personales no avisan a nadie: solo las ve su dueño.
     */
    public function avisarGuiaNueva(Guia $guia): void
    {
        $guia->loadMissing('grupo');
        $grupo = $guia->grupo;

        if ($guia->origen !== 'grupo' || $grupo === null) {
            return;
        }

        $mensaje = sprintf(
            'Tu instructor subió la guía «%s» al grupo «%s».',
            $this->recortar($guia->nombre),
            $this->recortar($grupo->nombre)
        );

        // numero_semana es la semana que el grupo ya puede leer de esta guía
        $this->crearParaEstudiantes($grupo, self::TIPO_GUIA_NUEVA, [
            [$mensaje, $this->datosDeGuia($grupo, $guia, (int) $grupo->semana_actual)],
        ]);
    }

    /**
     * Avisa a cada estudiante del grupo que puede leer una semana nueva.
     * Se crea un aviso por cada guía del grupo que tiene esa semana, para enlazar
     * directo a ella; si ninguna guía la tiene, no se avisa. Si el instructor saltó
     * varias semanas, el mensaje dice "hasta la semana N".
     */
    public function avisarSemanaLiberada(Grupo $grupo, int $semana, ?int $semanaAnterior = null): void
    {
        $guias = $grupo->guias()
            ->whereHas('semanas', fn ($semanas) => $semanas->where('numero_semana', $semana))
            ->orderBy('id')
            ->get(['id', 'nombre', 'grupo_id']);

        $hasta = $semanaAnterior !== null && $semana - $semanaAnterior > 1 ? 'hasta ' : '';

        $avisos = $guias->map(fn (Guia $guia) => [
            sprintf(
                'Ya puedes ver %sla semana %d de «%s» en «%s».',
                $hasta,
                $semana,
                $this->recortar($guia->nombre),
                $this->recortar($grupo->nombre)
            ),
            $this->datosDeGuia($grupo, $guia, $semana),
        ])->all();

        $this->crearParaEstudiantes($grupo, self::TIPO_SEMANA_LIBERADA, $avisos);
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
     * Guarda los avisos para todos los estudiantes del grupo en una sola consulta,
     * en lugar de una por estudiante. insert() no pasa por el modelo, así que
     * datos se convierte a JSON y las fechas se ponen aquí.
     *
     * @param array<int, array{0: string, 1: array}> $avisos pares [mensaje, datos]
     */
    private function crearParaEstudiantes(Grupo $grupo, string $tipo, array $avisos): void
    {
        $estudiantes = $grupo->estudiantes()->pluck('users.id');

        if ($avisos === [] || $estudiantes->isEmpty()) {
            return;
        }

        $ahora = now();
        $filas = [];

        foreach ($estudiantes as $estudianteId) {
            foreach ($avisos as [$mensaje, $datos]) {
                $filas[] = [
                    'usuario_id' => $estudianteId,
                    'tipo'       => $tipo,
                    'mensaje'    => $mensaje,
                    'datos'      => json_encode($datos, JSON_UNESCAPED_UNICODE),
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }
        }

        // De a 500 filas, para no armar una consulta gigante en un grupo muy grande
        foreach (array_chunk($filas, 500) as $lote) {
            Notificacion::insert($lote);
        }
    }

    /**
     * Datos que usa el frontend para enlazar el aviso con la guía y la semana.
     */
    private function datosDeGuia(Grupo $grupo, Guia $guia, int $numeroSemana): array
    {
        return [
            'grupo_id'      => $grupo->id,
            'grupo'         => $grupo->nombre,
            'guia_id'       => $guia->id,
            'guia'          => $guia->nombre,
            'numero_semana' => $numeroSemana,
        ];
    }

    /**
     * Acorta un nombre largo para que el mensaje quepa en su columna.
     */
    private function recortar(string $texto): string
    {
        return Str::limit($texto, self::LARGO_NOMBRE);
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
