<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use App\Models\MiniProyecto;
use App\Models\Guia;
use App\Models\GuiaTema;
use App\Traits\VerificaAccesoAGuia;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

class MiniProyectoController extends Controller
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
     * Genera mini proyectos practicos para una guia. Si se envia tema_id,
     * los proyectos se enfocan en ese tema puntual; si no, se generan de
     * forma general con base en todos los temas de la guia.
     */
    public function generarMiniProyecto(Request $request)
    {
        try {
            $request->validate([
                'guia_id' => 'required|integer|exists:guias,id',
                'tema_id' => 'nullable|integer|exists:guia_temas,id',
            ]);

            $guia = Guia::findOrFail($request->guia_id);
            $this->verificarAccesoAGuia($guia);

            $tema = null;
            if ($request->filled('tema_id')) {
                $tema = GuiaTema::findOrFail($request->tema_id);

                // Evita que alguien pida un tema_id valido pero de OTRA guia.
                if ($tema->guia_id !== $guia->id) {
                    return response()->json([
                        'message' => 'El tema indicado no pertenece a la guia indicada.',
                    ], 422);
                }
            }

            [$nombreTema, $contenidoTema] = $this->resolverContenido($guia, $tema);

            $proyectos = $this->geminiService->generarMiniProyectos($nombreTema, $contenidoTema);

            $guardados = [];
            foreach ($proyectos as $item) {
                $guardados[] = MiniProyecto::create([
                    'guia_id'     => $guia->id,
                    'tema_id'     => $tema?->id,
                    'titulo'      => $item['titulo'] ?? '',
                    'descripcion' => $item['descripcion'] ?? '',
                    'dificultad'  => $item['dificultad'] ?? 'basico',
                    'contenido'   => $item['contenido'] ?? '',
                ]);
            }

            return response()->json([
                'message'        => 'Mini proyectos generados correctamente',
                'mini_proyectos' => $guardados,
            ], 201);

        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Error en MiniProyectoController', ['mensaje' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al generar mini proyectos',
            ], 500);
        }
    }

    /**
     * Lista los mini proyectos ya generados para una guia especifica,
     * verificando que el estudiante tenga acceso a ella.
     */
    public function porGuia(int $guiaId)
    {
        try {
            $guia = Guia::findOrFail($guiaId);
            $this->verificarAccesoAGuia($guia);

            $miniProyectos = MiniProyecto::where('guia_id', $guiaId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'mini_proyectos' => $miniProyectos,
            ], 200);

        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Error listando mini proyectos', ['mensaje' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al obtener los mini proyectos',
            ], 500);
        }
    }

    /**
     * Decide que contenido enviarle a Gemini: si hay un tema puntual, usa
     * su nombre y descripcion; si no, concatena los temas de toda la guia
     * para dar contexto general (limitado para no exceder el prompt).
     */
    private function resolverContenido(Guia $guia, ?GuiaTema $tema): array
    {
        if ($tema) {
            return [$tema->nombre_tema, $tema->descripcion];
        }

        $temas = GuiaTema::where('guia_id', $guia->id)->orderBy('orden')->get();

        $contenidoGeneral = $temas
            ->map(fn ($t) => "- {$t->nombre_tema}: {$t->descripcion}")
            ->implode("\n");

        return [null, $contenidoGeneral];
    }
}