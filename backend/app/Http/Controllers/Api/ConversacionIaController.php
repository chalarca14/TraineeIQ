<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConversacionIa;
use App\Models\MensajeIa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConversacionIaController extends Controller
{
    /**
     * Listar todas las conversaciones del estudiante autenticado.
     */
    public function index(Request $request)
    {
        $estudianteId = Auth::id();

        $conversaciones = ConversacionIa::where('estudiante_id', $estudianteId)
            ->with('guia:id,nombre')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $conversaciones
        ], 200);
    }

    /**
     * Crear una nueva conversación (guiada o libre).
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'tipo' => 'required|in:guiada,libre',
            'guia_id' => 'nullable|exists:guias,id',
        ]);

        $conversacion = ConversacionIa::create([
            'estudiante_id' => Auth::id(),
            'guia_id' => $request->guia_id,
            'tipo' => $request->tipo,
            'titulo' => $request->titulo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversación creada exitosamente',
            'data' => $conversacion
        ], 201);
    }

    /**
     * Mostrar una conversación específica junto con sus mensajes.
     */
    public function show(int $id)
    {
        $conversacion = ConversacionIa::where('estudiante_id', Auth::id())
            ->with(['mensajes' => function($query) {
                $query->orderBy('created_at', 'asc');
            }, 'guia'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $conversacion
        ], 200);
    }

    /**
     * Enviar un mensaje dentro de una conversación y registrar la respuesta de Gemini.
     */
    public function enviarMensaje(Request $request, int $id)
    {
        $request->validate([
            'contenido' => 'required|string',
        ]);

        $conversacion = ConversacionIa::where('estudiante_id', Auth::id())->findOrFail($id);

        // 1. Guardar el mensaje enviado por el usuario
        $mensajeUsuario = MensajeIa::create([
            'conversacion_id' => $conversacion->id,
            'emisor' => 'usuario',
            'contenido' => $request->contenido,
        ]);

        // 2. Determinar la estrategia según el tipo de conversación (Guiada o Libre)
        // y generar la respuesta usando tu GeminiService centralizado
        try {
            if ($conversacion->tipo === 'guiada') {
                $estrategia = new \App\Services\Chat\GuiadaStrategy();
            } else {
                $estrategia = new \App\Services\Chat\LibreStrategy();
            }

            $respuestaTexto = $estrategia->generarRespuesta($conversacion, $request->contenido);
        } catch (\Exception $e) {
            Log::error('Error en estrategia de chat IA: ' . $e->getMessage());
            $respuestaTexto = 'Ocurrió un error al procesar tu solicitud con la IA.';
        }

        // 3. Guardar la respuesta real generada
        $mensajeIa = MensajeIa::create([
            'conversacion_id' => $conversacion->id,
            'emisor' => 'ia',
            'contenido' => $respuestaTexto,
        ]);

        $conversacion->touch();

        return response()->json([
            'success' => true,
            'data' => [
                'mensaje_usuario' => $mensajeUsuario,
                'mensaje_ia' => $mensajeIa,
            ]
        ], 201);
    }

    /**
     * Eliminar una conversación y sus mensajes en cascada.
     */
    public function destroy(int $id)
    {
        $conversacion = ConversacionIa::where('estudiante_id', Auth::id())->findOrFail($id);
        $conversacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversación eliminada correctamente'
        ], 200);
    }
}