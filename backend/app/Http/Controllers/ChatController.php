<?php

namespace App\Http\Controllers;

use App\Models\ConversacionIa;
use App\Models\MensajeIa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    // Listar las conversaciones del estudiante autenticado
    public function index(Request $request)
    {
        $conversaciones = ConversacionIa::where('estudiante_id', $request->user()->id)
            ->with('guia:id,nombre')
            ->latest()
            ->get();

        return response()->json($conversaciones, 200);
    }

    // Crear una nueva conversación
    public function storeConversacion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guia_id' => 'nullable|exists:guias,id',
            'tipo' => 'required|in:guiada,libre',
            'titulo' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $conversacion = ConversacionIa::create([
            'estudiante_id' => $request->user()->id,
            'guia_id' => $request->guia_id,
            'tipo' => $request->tipo,
            'titulo' => $request->titulo,
        ]);

        return response()->json([
            'message' => 'Conversación iniciada',
            'conversacion' => $conversacion
        ], 201);
    }

    // Ver los mensajes de una conversación específica
    public function show(Request $request, int $id)
    {
        $conversacion = ConversacionIa::where('id', $id)
            ->where('estudiante_id', $request->user()->id)
            ->with('mensajes')
            ->first();

        if (!$conversacion) {
            return response()->json(['message' => 'Conversación no encontrada'], 404);
        }

        return response()->json($conversacion, 200);
    }

    // Enviar un mensaje y registrar respuesta simulada de IA
    public function enviarMensaje(Request $request, int $conversacionId)
    {
        $validator = Validator::make($request->all(), [
            'mensaje' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $conversacion = ConversacionIa::where('id', $conversacionId)
            ->where('estudiante_id', $request->user()->id)
            ->first();

        if (!$conversacion) {
            return response()->json(['message' => 'Conversación no encontrada'], 404);
        }

        DB::beginTransaction();
        try {
            // 1. Guardar mensaje del usuario
            $userMsg = MensajeIa::create([
                'conversacion_id' => $conversacion->id,
                'emisor' => 'usuario',
                'contenido' => $request->mensaje,
            ]);

            // 2. Simulación de respuesta de la IA (puedes conectar Gemini/OpenAI después)
            $iaMsg = MensajeIa::create([
                'conversacion_id' => $conversacion->id,
                'emisor' => 'ia',
                'contenido' => "Entendido. Sobre tu consulta: '{$request->mensaje}', recuerda aplicar las buenas prácticas de arquitectura y seguridad.",
            ]);

            DB::commit();

            return response()->json([
                'usuario' => $userMsg,
                'ia' => $iaMsg,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al procesar el mensaje', 'error' => $e->getMessage()], 500);
        }
    }
}