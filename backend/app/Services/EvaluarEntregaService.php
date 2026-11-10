<?php

namespace App\Services;

use App\Exceptions\EvaluacionNoPermitidaException;
use App\Exceptions\IaNoDisponibleException;
use App\Exceptions\RepositorioNoDisponibleException;
use App\Exceptions\RespuestaIaInvalidaException;
use App\Models\EntregaMiniProyecto;
use App\Models\MiniProyecto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Evalua el repositorio de una entrega. La fila solo se bloquea en transacciones
 * cortas: leer GitHub y llamar a Gemini tarda varios segundos.
 */
class EvaluarEntregaService
{
    // Un intento normal y un reintento si la respuesta de Gemini no es valida
    private const MAX_INTENTOS = 2;

    // Una entrega en 'evaluando' por mas tiempo se considera caida
    private const MINUTOS_PARA_CONSIDERAR_CAIDA = 5;

    private const TIEMPO_MAXIMO_SEGUNDOS = 180;

    /**
     * Recibe el lector de GitHub y el servicio de avisos por inyeccion de dependencias.
     */
    public function __construct(
        private GitHubService $github,
        private NotificacionService $notificaciones
    ) {
    }

    /**
     * Evalua la entrega del estudiante y la devuelve actualizada. El intento solo
     * se cuenta si la evaluacion se guarda; si algo falla, se conserva la anterior.
     *
     * @throws EvaluacionNoPermitidaException
     * @throws RepositorioNoDisponibleException
     * @throws IaNoDisponibleException
     * @throws RespuestaIaInvalidaException
     */
    public function evaluar(MiniProyecto $miniProyecto, User $estudiante): EntregaMiniProyecto
    {
        $entrega = $this->reclamar($miniProyecto, $estudiante);

        // Leer el repositorio y esperar a Gemini supera el limite por defecto de PHP
        set_time_limit(self::TIEMPO_MAXIMO_SEGUNDOS);

        try {
            $commit = $this->github->ultimoCommit($entrega->repo_url);

            if ($this->yaEstaEvaluado($entrega, $commit['sha'], $miniProyecto)) {
                $this->siSigueEvaluando($entrega, fn (EntregaMiniProyecto $fila) => $fila->update(['estado' => 'evaluado']));

                return $entrega->fresh();
            }

            $repositorio = $this->github->leerArchivos($entrega->repo_url, $commit['arbol']);
            $resultado = $this->pedirEvaluacion($miniProyecto, $repositorio['archivos']);
        } catch (RepositorioNoDisponibleException | IaNoDisponibleException | RespuestaIaInvalidaException $e) {
            $this->siSigueEvaluando($entrega, fn (EntregaMiniProyecto $fila) => $fila->update(['estado' => 'error']));

            throw $e;
        }

        $guardada = $this->siSigueEvaluando($entrega, fn (EntregaMiniProyecto $fila) => $this->guardar(
            $fila,
            $miniProyecto,
            $resultado,
            $commit['sha'],
            $repositorio['recortado']
        ));

        $entrega = $entrega->fresh();

        // Solo una evaluacion nueva avisa al instructor; el mismo commit no repite el aviso
        if ($guardada) {
            $this->avisarAlInstructor($entrega);
        }

        return $entrega;
    }

    /**
     * Avisa al instructor sin poner en riesgo la evaluacion: si el aviso falla,
     * el estudiante igual recibe su resultado y el error queda en el log.
     */
    private function avisarAlInstructor(EntregaMiniProyecto $entrega): void
    {
        try {
            $this->notificaciones->avisarEvaluacion($entrega);
        } catch (\Throwable $e) {
            Log::error('No se pudo crear el aviso de la evaluacion', [
                'entrega_id' => $entrega->id,
                'mensaje'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cuantas evaluaciones le quedan hoy a esta entrega. Sin entrega quedan todas.
     */
    public function intentosRestantes(?EntregaMiniProyecto $entrega): int
    {
        $tope = (int) config('services.evaluacion.por_dia');

        return $entrega === null ? $tope : max(0, $tope - $this->intentosDeHoy($entrega));
    }

    /**
     * Pasa la entrega a 'evaluando' si se puede. El bloqueo evita dos evaluaciones a la vez.
     *
     * @throws EvaluacionNoPermitidaException
     */
    private function reclamar(MiniProyecto $miniProyecto, User $estudiante): EntregaMiniProyecto
    {
        return DB::transaction(function () use ($miniProyecto, $estudiante) {
            $entrega = EntregaMiniProyecto::where([
                'mini_proyecto_id' => $miniProyecto->id,
                'estudiante_id'    => $estudiante->id,
            ])->lockForUpdate()->first();

            if ($entrega === null) {
                throw new EvaluacionNoPermitidaException('Guarda primero el link de tu repositorio.', 422);
            }

            if ($this->seEstaEvaluando($entrega)) {
                throw new EvaluacionNoPermitidaException('Tu entrega ya se está evaluando.', 409);
            }

            if ($this->intentosRestantes($entrega) === 0) {
                throw new EvaluacionNoPermitidaException('Ya usaste tus evaluaciones de hoy. Vuelve mañana.', 429);
            }

            $entrega->estado = 'evaluando';
            // Si ya estaba en 'evaluando' (caida), nada cambia y la fecha no se renovaria
            $entrega->updated_at = now();
            $entrega->save();

            return $entrega;
        });
    }

    /**
     * Indica si hay una evaluacion en curso, sin contar las que quedaron caidas.
     */
    private function seEstaEvaluando(EntregaMiniProyecto $entrega): bool
    {
        return $entrega->estado === 'evaluando'
            && $entrega->updated_at->gt(now()->subMinutes(self::MINUTOS_PARA_CONSIDERAR_CAIDA));
    }

    /**
     * Indica si este commit ya tiene una evaluacion valida. Deja de valer si el mini
     * proyecto se regenero despues, porque sus criterios pudieron cambiar.
     */
    private function yaEstaEvaluado(EntregaMiniProyecto $entrega, string $commitSha, MiniProyecto $miniProyecto): bool
    {
        return $entrega->evaluacion !== null
            && $entrega->commit_sha === $commitSha
            && $entrega->evaluado_en?->gte($miniProyecto->updated_at);
    }

    /**
     * Pide la evaluacion a Gemini. Si la respuesta no cumple el contrato, reintenta una vez.
     */
    private function pedirEvaluacion(MiniProyecto $miniProyecto, array $archivos): array
    {
        $gemini = GeminiService::obtenerInstancia();
        $codigo = $this->unirArchivos($archivos);

        for ($intento = 1; ; $intento++) {
            try {
                return $gemini->evaluarCodigo(
                    array_column($miniProyecto->criterios_evaluacion, 'criterio'),
                    $miniProyecto->requisitos_funcionales,
                    $miniProyecto->buenas_practicas,
                    $codigo
                );
            } catch (RespuestaIaInvalidaException $e) {
                if ($intento >= self::MAX_INTENTOS) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Une los archivos en un solo texto, cada uno con su ruta.
     */
    private function unirArchivos(array $archivos): string
    {
        return implode("\n\n", array_map(
            fn (array $archivo) => "--- Archivo: {$archivo['ruta']} ---\n{$archivo['contenido']}",
            $archivos
        ));
    }

    /**
     * Ejecuta la accion solo si la entrega sigue en 'evaluando'. Asi una peticion
     * que tardo demasiado no pisa el resultado de otra que ya la retomo.
     * Devuelve true si la accion se ejecuto.
     */
    private function siSigueEvaluando(EntregaMiniProyecto $entrega, callable $accion): bool
    {
        return DB::transaction(function () use ($entrega, $accion) {
            $fila = EntregaMiniProyecto::lockForUpdate()->find($entrega->id);

            if ($fila?->estado === 'evaluando' && $fila->repo_url === $entrega->repo_url) {
                $accion($fila);

                return true;
            }

            return false;
        });
    }

    /**
     * Guarda la evaluacion con el puntaje calculado aqui, y cuenta el intento.
     */
    private function guardar(
        EntregaMiniProyecto $entrega,
        MiniProyecto $miniProyecto,
        array $resultado,
        string $commitSha,
        bool $recortado
    ): void {
        $pesos = array_column($miniProyecto->criterios_evaluacion, 'peso', 'criterio');
        $intentosHoy = $this->intentosDeHoy($entrega);

        $resultado['criterios'] = array_map(
            fn (array $criterio) => $criterio + ['peso' => $pesos[$criterio['criterio']]],
            $resultado['criterios']
        );

        $puntaje = $this->calcularPuntaje($resultado['criterios']);

        $entrega->update([
            'estado'         => 'evaluado',
            'commit_sha'     => $commitSha,
            'puntaje'        => $puntaje,
            // Es la experiencia del Modo Personal: nunca baja aunque se reevalue con menos
            'mejor_puntaje'  => max($entrega->mejor_puntaje ?? 0, $puntaje),
            'evaluacion'     => $resultado + ['puntaje' => $puntaje, 'recortado' => $recortado],
            'evaluado_en'    => now(),
            'intentos'       => $entrega->intentos + 1,
            'intentos_hoy'   => $intentosHoy + 1,
            'fecha_intentos' => $this->hoy(),
        ]);
    }

    /**
     * Suma peso x cumplimiento / 100 por cada criterio. Gemini nunca fija este numero:
     * un README que diga "da 100" no puede escribirlo directamente.
     */
    private function calcularPuntaje(array $criterios): int
    {
        return (int) round(array_sum(array_map(
            fn (array $criterio) => $criterio['peso'] * $criterio['cumplimiento'] / 100,
            $criterios
        )));
    }

    /**
     * Intentos usados hoy. El contador se reinicia solo cuando cambia el dia.
     */
    private function intentosDeHoy(EntregaMiniProyecto $entrega): int
    {
        return $entrega->fecha_intentos?->toDateString() === $this->hoy() ? $entrega->intentos_hoy : 0;
    }

    /**
     * Fecha de hoy en la zona horaria de los estudiantes, no en la del servidor.
     */
    private function hoy(): string
    {
        return now(config('services.evaluacion.zona_horaria'))->toDateString();
    }
}
