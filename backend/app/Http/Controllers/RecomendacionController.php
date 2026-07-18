<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use App\Models\Recomendacion;
use App\Models\Guia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecomendacionController extends Controller
{
    private GeminiService $geminiService;

    /**
     * Inyecta la unica instancia de GeminiService mediante el patron Singleton.
     */
    public function __construct()
    {
        $this->geminiService = GeminiService::obtenerInstancia();
    }

    /**
     * Genera recomendaciones personalizadas para el estudiante
     * segun la guia de su grupo y su semana actual.
     */
    public function generarRecomendacion(Request $request)
    {
        try {
            // Validar que lleguen los datos necesarios
            $request->validate([
                'guia_id'       => 'required|integer|exists:guias,id',
                'semana_actual' => 'required|integer|min:1',
            ]);

            // Buscar la guia en la base de datos
            $guia = Guia::findOrFail($request->guia_id);

            // Extraer el contenido de texto del PDF de la guia
            $contenidoGuia = $this->extraerContenidoPdf($guia->archivo_pdf);

            // Pedirle a Gemini que genere las recomendaciones
            $recomendaciones = $this->geminiService->generarRecomendaciones(
                $contenidoGuia,
                $request->semana_actual
            );

            // Guardar las recomendaciones en la base de datos
            $recomendacion = Recomendacion::create([
                'estudiante_id' => auth()->id(),
                'guia_id'       => $request->guia_id,
                'semana'        => $request->semana_actual,
                'contenido'     => json_encode($recomendaciones),
            ]);

            return response()->json([
                'message'        => 'Recomendaciones generadas correctamente',
                'recomendacion'  => $recomendaciones,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error en RecomendacionController', ['mensaje' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al generar recomendaciones',
            ], 500);
        }
    }

    /**
     * Retorna el historial de recomendaciones recibidas por el estudiante autenticado.
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

    /**
     * Extrae el contenido de texto de un archivo PDF de la guia.
     */
    private function extraerContenidoPdf(string $rutaPdf): string
    {
        try {
            $rutaCompleta = storage_path('app/public/' . $rutaPdf);

            // Verificar que el archivo existe antes de leerlo
            if (!file_exists($rutaCompleta)) {
                Log::error('PDF no encontrado', ['ruta' => $rutaCompleta]);
                return '';
            }

            // Usar smalot/pdfparser para extraer el texto del PDF
            $parser  = new \Smalot\PdfParser\Parser();
            $pdf     = $parser->parseFile($rutaCompleta);
            $texto   = $pdf->getText();

            return $texto;

        } catch (\Exception $e) {
            Log::error('Error extrayendo PDF', ['mensaje' => $e->getMessage()]);
            return '';
        }
    }
}