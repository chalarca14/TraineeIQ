<?php

namespace App\Services;

use App\Exceptions\IaNoDisponibleException;
use App\Exceptions\RespuestaIaInvalidaException;
use App\Models\GuiaSemana;
use App\Models\MiniProyecto;
use App\Models\Recomendacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Genera las recomendaciones y los mini proyectos de UNA semana. Cada semana va
 * en su propia peticion para no pasar el tiempo limite.
 *
 * La fila solo se bloquea en transacciones cortas: Gemini tarda varios segundos,
 * y durante ese tiempo no debe quedar una transaccion abierta.
 */
class GenerarContenidoSemanaService
{
    // Un intento normal y un reintento si la respuesta de Gemini no es valida
    private const MAX_INTENTOS = 2;

    // Una semana en 'generando' por mas tiempo se considera caida
    private const MINUTOS_PARA_CONSIDERAR_CAIDA = 5;

    private const TIEMPO_MAXIMO_SEGUNDOS = 180;

    /**
     * Genera la semana si le corresponde y la devuelve con su estado actualizado.
     * Se puede repetir sin duplicar datos. Con $forzar se regenera aunque este en
     * 'listo' o 'error'; la Policy decide antes quien puede pedirlo.
     *
     * @throws IaNoDisponibleException Si Gemini no responde: la semana vuelve a pendiente.
     */
    public function generar(GuiaSemana $semana, bool $forzar = false): GuiaSemana
    {
        $reclamada = $this->reclamar($semana, $forzar);

        if ($reclamada === null) {
            return $semana->fresh() ?? $semana;
        }

        // El hash inicial permite detectar si el PDF se reemplazo durante la generacion
        $hash = $reclamada->hash_contenido;

        // Gemini puede tardar mas que el limite por defecto de PHP
        set_time_limit(self::TIEMPO_MAXIMO_SEGUNDOS);

        try {
            $contenido = $this->pedirContenido($reclamada);
        } catch (IaNoDisponibleException $e) {
            // Es temporal: queda pendiente y se reintenta cuando alguien abra la semana
            $this->siSigueVigente($reclamada, $hash, fn (GuiaSemana $fila) => $fila->update([
                'estado_generacion' => 'pendiente',
            ]));

            throw $e;
        } catch (RespuestaIaInvalidaException $e) {
            Log::warning('Gemini devolvio contenido invalido', [
                'guia_semana_id' => $reclamada->id,
                'mensaje'        => $e->getMessage(),
            ]);

            $this->siSigueVigente($reclamada, $hash, fn (GuiaSemana $fila) => $fila->update([
                'estado_generacion' => 'error',
                'error_mensaje'     => 'La IA devolvió un contenido inválido después de dos intentos.',
            ]));

            return $reclamada->fresh() ?? $reclamada;
        }

        $this->siSigueVigente($reclamada, $hash, fn (GuiaSemana $fila) => $this->guardar($fila, $contenido));

        return $reclamada->fresh() ?? $reclamada;
    }

    /**
     * Pasa la semana a 'generando' si le corresponde. El bloqueo evita que dos
     * personas la generen a la vez. Retorna null si no hay nada que generar.
     */
    private function reclamar(GuiaSemana $semana, bool $forzar): ?GuiaSemana
    {
        return DB::transaction(function () use ($semana, $forzar) {
            $fila = GuiaSemana::lockForUpdate()->find($semana->id);

            if ($fila === null || ! $this->debeGenerarse($fila, $forzar)) {
                return null;
            }

            $fila->fill(['estado_generacion' => 'generando', 'error_mensaje' => null]);
            // Si ya estaba en 'generando' nada cambia y Eloquent no actualizaria la fecha,
            // y sin fecha nueva la semana seguiria pareciendo caida para la siguiente peticion
            $fila->updated_at = now();
            $fila->save();

            return $fila;
        });
    }

    /**
     * Decide si la semana se genera. Una generacion en curso nunca se interrumpe.
     * Sin $forzar solo se genera lo pendiente: reintentar un 'error' es decision del dueño,
     * para que cada estudiante que abra la semana no gaste cuota de Gemini.
     */
    private function debeGenerarse(GuiaSemana $semana, bool $forzar): bool
    {
        if ($semana->estado_generacion === 'generando') {
            return $semana->updated_at->lte(now()->subMinutes(self::MINUTOS_PARA_CONSIDERAR_CAIDA));
        }

        return $forzar || $semana->estado_generacion === 'pendiente';
    }

    /**
     * Pide el contenido a Gemini. Si la respuesta no cumple el contrato, reintenta una vez.
     */
    private function pedirContenido(GuiaSemana $semana): array
    {
        $gemini = GeminiService::obtenerInstancia();

        for ($intento = 1; ; $intento++) {
            try {
                return $gemini->generarContenidoSemana($semana->titulo, $semana->resumen, $semana->contenido_texto);
            } catch (RespuestaIaInvalidaException $e) {
                if ($intento >= self::MAX_INTENTOS) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Ejecuta la accion solo si la semana sigue en 'generando' con el mismo texto.
     * Si el PDF se reemplazo mientras Gemini trabajaba, el resultado ya es viejo y se
     * descarta. Tambien se descarta si otra peticion ya termino esta misma semana.
     */
    private function siSigueVigente(GuiaSemana $semana, string $hash, callable $accion): void
    {
        DB::transaction(function () use ($semana, $hash, $accion) {
            $fila = GuiaSemana::lockForUpdate()->find($semana->id);

            if ($fila?->estado_generacion === 'generando' && $fila->hash_contenido === $hash) {
                $accion($fila);
            }
        });
    }

    /**
     * Guarda el contenido y marca la semana como lista. Usa updateOrCreate por
     * (semana, orden) sin borrar filas: borrar un mini proyecto borraria sus entregas.
     */
    private function guardar(GuiaSemana $semana, array $contenido): void
    {
        foreach ($contenido['recomendaciones'] as $recomendacion) {
            Recomendacion::updateOrCreate(
                ['guia_semana_id' => $semana->id, 'orden' => $recomendacion['orden']],
                [
                    'guia_id'   => $semana->guia_id,
                    'nivel'     => $recomendacion['nivel'],
                    'contenido' => [
                        'titulo'      => $recomendacion['titulo'],
                        'tema'        => $recomendacion['tema'],
                        'tema_semana' => $recomendacion['tema_semana'],
                        'por_que'     => $recomendacion['por_que'],
                        'descripcion' => $recomendacion['descripcion'],
                    ],
                ]
            );
        }

        foreach ($contenido['mini_proyectos'] as $proyecto) {
            MiniProyecto::updateOrCreate(
                ['guia_semana_id' => $semana->id, 'orden' => $proyecto['orden']],
                ['guia_id' => $semana->guia_id] + $proyecto
            );
        }

        $semana->update(['estado_generacion' => 'listo', 'error_mensaje' => null]);
    }
}
