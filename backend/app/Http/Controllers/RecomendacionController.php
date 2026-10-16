<?php

namespace App\Http\Controllers;

use App\Traits\VerificaAccesoAGuia;
use App\Services\GeminiService;
use App\Models\Recomendacion;
use App\Models\GuiaTema;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

class RecomendacionController extends Controller
{
    use VerificaAccesoAGuia;

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

            $recomendaciones = $this->geminiService->generarRecomendaciones(
                $tema->nombre_tema,
                $tema->descripcion,
                $request->semana_actual
            );

            $guardadas = [];
            foreach ($recomendaciones as $item) {
                $guardadas[] = Recomendacion::create([
                    'estudiante_id' => auth()->id(),
                    'guia_id'       => $tema->guia_id,
                    'tema_id'       => $tema->id,
                    'semana'        => $request->semana_actual,
                    'nivel'         => $item['nivel'] ?? 'recomendado',
                    'contenido'     => json_encode([
                        'titulo'      => $item['titulo'] ?? '',
                        'descripcion' => $item['descripcion'] ?? '',
                    ]),
                ]);
            }

            return response()->json([
                'message'         => 'Recomendaciones generadas correctamente',
                'recomendaciones' => $guardadas,
            ], 201);

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
     * Retorna el historial de recomendaciones del estudiante autenticado.
     */
    public function historial()
    {
        try {
            $historial = Recomendacion::where('estudiante_id', auth()->id())
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