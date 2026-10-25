<?php

namespace App\Http\Controllers;

use App\Traits\VerificaAccesoAGuia;
use App\Services\GeminiService;
use App\Models\Recomendacion;
use App\Models\GuiaTema;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecomendacionController extends Controller
{
    use VerificaAccesoAGuia;

    // Valores permitidos de la columna recomendaciones.nivel
    private const NIVELES = ['basico', 'recomendado', 'importante', 'avanzado'];

    private GeminiService $geminiService;

    /**
     * Inyecta la unica instancia de GeminiService mediante el patron Singleton.
     */
    public function __construct()
    {
        $this->geminiService = GeminiService::obtenerInstancia();
    }

    /**
     * Genera recomendaciones personalizadas para UN tema especifico de una guia.
     * CAMBIO v3: ya no recibe guia_id + parsea el PDF completo. Ahora recibe tema_id
     * (guia_temas), cuyo contenido ya fue segmentado por Integrante 3 al subir la guia.
     * semana_actual es opcional porque en Modo Personal no existe semana asignada.
     * CAMBIO v4: valida el nivel que devuelve Gemini, responde 503 si la IA no contesta
     * y guarda todas las recomendaciones en una sola transaccion.
     * CAMBIO v6: si el estudiante YA tiene recomendaciones de este tema, se devuelven esas
     * (200) y no se llama a Gemini ni se guardan filas nuevas. Un candado evita que dos
     * clics seguidos generen el mismo tema dos veces.
     */
    public function generarRecomendacion(Request $request)
    {
        try {
            $request->validate([
                'tema_id'       => 'required|integer|exists:guia_temas,id',
                'semana_actual' => 'nullable|integer|min:1',
            ]);

            $tema = GuiaTema::with('guia')->findOrFail($request->tema_id);

            // Verifica que el estudiante puede ver la guia dueña de este tema:
            // o es su guia personal, o pertenece a un grupo del que es miembro.
            $this->verificarAccesoAGuia($tema->guia);

            $estudianteId = $request->user()->id;

            // Candado: solo una generacion a la vez por estudiante y tema (evita el doble clic)
            $candado = Cache::lock("generar-recomendaciones:{$estudianteId}:{$tema->id}", 150);

            if (! $candado->get()) {
                return response()->json([
                    'message' => 'Ya se están generando las recomendaciones de este tema, espera un momento.',
                ], 409);
            }

            try {
                // Si ya existen recomendaciones de este tema, se muestran esas y no se genera nada nuevo
                $existentes = Recomendacion::where('estudiante_id', $estudianteId)
                    ->where('tema_id', $tema->id)
                    ->orderBy('id')
                    ->get();

                if ($existentes->isNotEmpty()) {
                    return response()->json([
                        'message'         => 'Ya tenías recomendaciones para este tema',
                        'ya_existian'     => true,
                        'recomendaciones' => $existentes,
                    ], 200);
                }

                $recomendaciones = $this->geminiService->generarRecomendaciones(
                    $tema->nombre_tema,
                    (string) $tema->descripcion,
                    $request->semana_actual
                );

                // Si la IA no respondio, no se guarda nada y se avisa para que el usuario reintente
                if (empty($recomendaciones)) {
                    return response()->json([
                        'message' => 'La IA está ocupada, intenta de nuevo en un momento.',
                    ], 503);
                }

                // Todo se guarda en una transaccion: o quedan todas las recomendaciones o ninguna
                $guardadas = DB::transaction(function () use ($recomendaciones, $request, $tema, $estudianteId) {
                    $filas = [];

                    foreach ($recomendaciones as $item) {
                        $filas[] = Recomendacion::create([
                            'estudiante_id' => $estudianteId,
                            'guia_id'       => $tema->guia_id,
                            'tema_id'       => $tema->id,
                            'semana'        => $request->semana_actual,
                            // Gemini puede devolver un nivel fuera de la lista; en ese caso se usa 'recomendado'
                            'nivel'         => in_array($item['nivel'] ?? '', self::NIVELES, true)
                                ? $item['nivel']
                                : 'recomendado',
                            'contenido'     => json_encode([
                                'titulo'      => $item['titulo'] ?? '',
                                'descripcion' => $item['descripcion'] ?? '',
                            ], JSON_UNESCAPED_UNICODE),
                        ]);
                    }

                    return $filas;
                });

                return response()->json([
                    'message'         => 'Recomendaciones generadas correctamente',
                    'ya_existian'     => false,
                    'recomendaciones' => $guardadas,
                ], 201);
            } finally {
                // Siempre se libera el candado, incluso si algo fallo
                $candado->release();
            }

        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Error en RecomendacionController', ['mensaje' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al generar recomendaciones',
            ], 500);
        }
    }

    /**
     * Retorna el historial de recomendaciones del estudiante autenticado,
     * con el nombre del tema al que pertenece cada una.
     */
    public function historial(Request $request)
    {
        try {
            $historial = Recomendacion::with('tema:id,nombre_tema')
                ->where('estudiante_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'historial' => $historial,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error obteniendo historial', ['mensaje' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al obtener el historial',
            ], 500);
        }
    }
}