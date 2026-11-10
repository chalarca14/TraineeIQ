<?php

namespace App\Services;

use App\Exceptions\EntregaEnEvaluacionException;
use App\Models\EntregaMiniProyecto;
use App\Models\MiniProyecto;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Maneja el repositorio que cada estudiante entrega en un mini proyecto.
 * Hay una sola fila por estudiante y mini proyecto.
 */
class EntregaMiniProyectoService
{
    // Una entrega en 'evaluando' por mas tiempo se considera caida (decision 12 de la Fase 0)
    private const MINUTOS_PARA_CONSIDERAR_CAIDA = 5;

    /**
     * Guarda o cambia el repositorio. Si el link es otro, borra la evaluacion anterior
     * porque era de otro codigo, pero conserva los intentos: asi cambiar el link no
     * sirve para saltarse el tope diario.
     *
     * @throws EntregaEnEvaluacionException Si la entrega se esta evaluando.
     */
    public function guardarRepo(MiniProyecto $miniProyecto, User $estudiante, string $repoUrl): EntregaMiniProyecto
    {
        return DB::transaction(function () use ($miniProyecto, $estudiante, $repoUrl) {
            $llave = ['mini_proyecto_id' => $miniProyecto->id, 'estudiante_id' => $estudiante->id];

            $entrega = EntregaMiniProyecto::where($llave)->lockForUpdate()->first();

            if ($entrega === null) {
                // createOrFirst no falla si dos peticiones crean la misma entrega a la vez
                return EntregaMiniProyecto::createOrFirst($llave, ['repo_url' => $repoUrl]);
            }

            if ($entrega->repo_url === $repoUrl) {
                return $entrega;
            }

            if ($this->seEstaEvaluando($entrega)) {
                throw new EntregaEnEvaluacionException();
            }

            $entrega->update([
                'repo_url'    => $repoUrl,
                'commit_sha'  => null,
                'estado'      => 'pendiente',
                'puntaje'     => null,
                'evaluacion'  => null,
                'evaluado_en' => null,
            ]);

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
}
