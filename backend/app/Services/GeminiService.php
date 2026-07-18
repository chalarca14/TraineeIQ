<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private static ?GeminiService $instancia = null;
    private string $apiKey;
    private string $urlBase;

    /**
     * Constructor privado para aplicar el patron Singleton.
     * Solo puede ser instanciado desde el metodo obtenerInstancia().
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
     * Genera recomendaciones personalizadas usando Gemini API
     * segun el contenido de la guia y la semana actual del estudiante.
     */
    public function generarRecomendaciones(string $contenidoGuia, int $semanaActual): array
    {
        try {
            $prompt = $this->construirPrompt($contenidoGuia, $semanaActual);

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
                return $this->respuestaVacia();
            }

            return $this->procesarRespuesta($respuesta->json());

        } catch (\Exception $e) {
            Log::error('Excepcion en GeminiService', ['mensaje' => $e->getMessage()]);
            return $this->respuestaVacia();
        }
    }

    /**
     * Construye el prompt que se envia a Gemini con el contexto del estudiante.
     */
    private function construirPrompt(string $contenidoGuia, int $semanaActual): string
    {
        return "Eres un asistente academico del SENA. 
        Un estudiante de programacion esta en la semana {$semanaActual} de su formacion.
        El contenido de su guia de aprendizaje es el siguiente:
        {$contenidoGuia}
        
        Por favor genera:
        1. Una lista de 3 temas para profundizar relacionados con lo que estudia esta semana.
        2. Una lista de 3 mini proyectos practicos graduados por dificultad (facil, medio, reto).
        
        Responde unicamente en formato JSON con esta estructura exacta:
        {
            \"temas\": [\"tema1\", \"tema2\", \"tema3\"],
            \"proyectos\": [
                {\"nivel\": \"facil\", \"titulo\": \"...\", \"descripcion\": \"...\"},
                {\"nivel\": \"medio\", \"titulo\": \"...\", \"descripcion\": \"...\"},
                {\"nivel\": \"reto\", \"titulo\": \"...\", \"descripcion\": \"...\"}
            ]
        }";
    }

    /**
     * Procesa la respuesta JSON de Gemini y extrae el contenido util.
     */
    private function procesarRespuesta(array $datos): array
    {
        try {
            $texto = $datos['candidates'][0]['content']['parts'][0]['text'] ?? '';
            // Limpiar posibles bloques de codigo markdown que Gemini puede incluir
            $texto = preg_replace('/```json|```/', '', $texto);
            return json_decode(trim($texto), true) ?? $this->respuestaVacia();
        } catch (\Exception $e) {
            Log::error('Error procesando respuesta de Gemini', ['mensaje' => $e->getMessage()]);
            return $this->respuestaVacia();
        }
    }

    /**
     * Retorna una estructura vacia en caso de error para no romper el sistema.
     */
    private function respuestaVacia(): array
    {
        return [
            'temas' => [],
            'proyectos' => []
        ];
    }
}