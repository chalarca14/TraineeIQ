<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio centralizado (patron Singleton) para toda comunicacion con la API de Gemini.
 * CAMBIOS v3:
 * - generarRecomendaciones() ahora trabaja por TEMA (guia_temas), no por guia completa.
 * - Los mini proyectos se separan en su propio metodo, porque ahora tienen su propia tabla
 *   (mini_proyectos) con su propio campo de dificultad.
 * - Se agrega generarRespuestaChat(), metodo generico que usaran las estrategias del chat
 *   (GuiadaStrategy y LibreStrategy) para no duplicar la logica HTTP.
 */
class GeminiService
{
    private static ?GeminiService $instancia = null;
    private string $apiKey;
    private string $urlBase;

    /**
     * Constructor privado para aplicar el patron Singleton.
     */
    private function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->urlBase = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    }

    /**
     * Retorna la unica instancia de GeminiService (patron Singleton).
     */
    public static function obtenerInstancia(): GeminiService
    {
        if (self::$instancia === null) {
            self::$instancia = new GeminiService();
        }
        return self::$instancia;
    }

    /**
     * Genera recomendaciones academicas para UN tema especifico de una guia,
     * clasificadas por nivel: basico, recomendado, importante, avanzado.
     */
    public function generarRecomendaciones(string $nombreTema, string $contenidoTema, ?int $semanaActual = null): array
    {
        $prompt = $this->construirPromptRecomendaciones($nombreTema, $contenidoTema, $semanaActual);
        $texto = $this->llamarApi($prompt);

        if ($texto === null) {
            return [];
        }

        return $this->parsearJson($texto, []);
    }

    /**
     * Genera mini proyectos practicos para un tema (o para la guia completa si
     * $nombreTema es null), clasificados por dificultad: basico, intermedio, avanzado.
     */
    public function generarMiniProyectos(?string $nombreTema, string $contenidoTema): array
    {
        $prompt = $this->construirPromptMiniProyectos($nombreTema, $contenidoTema);
        $texto = $this->llamarApi($prompt);

        if ($texto === null) {
            return [];
        }

        return $this->parsearJson($texto, []);
    }

    /**
     * Metodo generico de bajo nivel: envia cualquier prompt ya armado a Gemini y
     * retorna texto plano (no JSON). Lo usaran GuiadaStrategy y LibreStrategy,
     * porque cada una arma su propio prompt segun sus propias reglas de negocio.
     */
    public function generarRespuestaChat(string $prompt): string
    {
        $texto = $this->llamarApi($prompt);

        if ($texto === null) {
            return 'No fue posible generar una respuesta en este momento. Intenta nuevamente.';
        }

        return trim($texto);
    }

    /**
     * Llama a la API REST de Gemini con un prompt ya construido.
     * Centraliza el manejo de errores HTTP para no repetir try-catch en cada metodo publico.
     */
    private function llamarApi(string $prompt): ?string
    {
        try {
            $respuesta = Http::post("{$this->urlBase}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($respuesta->failed()) {
                Log::error('Error al llamar a Gemini API', ['status' => $respuesta->status()]);
                return null;
            }

            return $respuesta->json('candidates.0.content.parts.0.text');

        } catch (\Exception $e) {
            Log::error('Excepcion en GeminiService', ['mensaje' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Prompt para recomendaciones: exige que Gemini clasifique cada sugerencia
     * segun el ENUM real de la columna recomendaciones.nivel.
     */
    private function construirPromptRecomendaciones(string $nombreTema, string $contenidoTema, ?int $semanaActual): string
    {
        $contextoSemana = $semanaActual
            ? "El estudiante se encuentra en la semana {$semanaActual} de su formacion."
            : "El estudiante esta estudiando este tema de forma independiente (Modo Personal), sin semana asignada.";

        return "Eres un asistente academico del SENA especializado en programacion.
        {$contextoSemana}
        El tema que esta estudiando actualmente es: \"{$nombreTema}\".
        Contenido de ese tema extraido de su guia:
        {$contenidoTema}

        Genera entre 3 y 5 recomendaciones de profundizacion para este tema especifico.
        Cada recomendacion debe tener un nivel EXACTAMENTE uno de estos valores:
        \"basico\", \"recomendado\", \"importante\", \"avanzado\".

        Responde UNICAMENTE en formato JSON, sin texto adicional, con esta estructura exacta:
        [
            {\"nivel\": \"basico\", \"titulo\": \"...\", \"descripcion\": \"...\"},
            {\"nivel\": \"recomendado\", \"titulo\": \"...\", \"descripcion\": \"...\"}
        ]";
    }

        /**
     * Prompt para mini proyectos: exige clasificacion segun el ENUM real de
     * mini_proyectos.dificultad, y separa descripcion (resumen) de contenido
     * (instrucciones completas), tal como lo define la tabla mini_proyectos.
     */
    private function construirPromptMiniProyectos(?string $nombreTema, string $contenidoTema): string
    {
        $etiquetaTema = $nombreTema ? "sobre el tema \"{$nombreTema}\"" : "sobre el contenido general de la guia";

        return "Eres un asistente academico del SENA especializado en programacion.
        Genera 3 mini proyectos practicos {$etiquetaTema}, uno por cada nivel de dificultad.
        Contenido de referencia:
        {$contenidoTema}

        La dificultad debe ser EXACTAMENTE uno de estos valores: \"basico\", \"intermedio\", \"avanzado\".
        \"descripcion\" es un resumen de una sola linea. \"contenido\" son las instrucciones
        completas paso a paso para que el estudiante desarrolle el proyecto.

        Responde UNICAMENTE en formato JSON, sin texto adicional, con esta estructura exacta:
        [
            {\"dificultad\": \"basico\", \"titulo\": \"...\", \"descripcion\": \"...\", \"contenido\": \"...\"},
            {\"dificultad\": \"intermedio\", \"titulo\": \"...\", \"descripcion\": \"...\", \"contenido\": \"...\"},
            {\"dificultad\": \"avanzado\", \"titulo\": \"...\", \"descripcion\": \"...\", \"contenido\": \"...\"}
        ]";
    }

    /**
     * Limpia bloques de codigo markdown que Gemini a veces agrega y decodifica el JSON.
     * Si algo falla, retorna el valor por defecto en vez de romper la aplicacion.
     */
    private function parsearJson(string $texto, array $porDefecto): array
    {
        try {
            $limpio = preg_replace('/```json|```/', '', $texto);
            $decodificado = json_decode(trim($limpio), true);
            return is_array($decodificado) ? $decodificado : $porDefecto;
        } catch (\Exception $e) {
            Log::error('Error parseando JSON de Gemini', ['mensaje' => $e->getMessage()]);
            return $porDefecto;
        }
    }
}