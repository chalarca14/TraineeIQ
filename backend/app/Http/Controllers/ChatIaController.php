<?php

namespace App\Http\Controllers;

use App\Exceptions\IaNoDisponibleException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ConversacionIa;
use App\Models\Guia;
use App\Models\MensajeIa;
use App\Services\Chat\ChatStrategy;
use App\Services\Chat\GuiadaStrategy;
use App\Services\Chat\LibreStrategy;
use App\Traits\VerificaAccesoAGuia;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class ChatIaController extends Controller
{
    use VerificaAccesoAGuia;

    /**
     * Crea una conversacion nueva, guiada (con guia_id) o libre (sin guia_id).
     * La regla exactamente-uno-o-ninguno se valida aqui mismo con 'prohibited_if'.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'tipo'    => 'required|in:guiada,libre',
                'guia_id' => 'required_if:tipo,guiada|prohibited_if:tipo,libre|integer|exists:guias,id',
            ]);

            if ($request->tipo === 'guiada') {
                $guia = Guia::findOrFail($request->guia_id);
                $this->verificarAccesoAGuia($guia);
            }

            $conversacion = ConversacionIa::create([
                'estudiante_id' => Auth::id(),
                'guia_id'       => $request->tipo === 'guiada' ? $request->guia_id : null,
                'tipo'          => $request->tipo,
                'titulo'        => $request->tipo === 'guiada'
                    ? 'Conversacion sobre ' . $guia->nombre
                    : 'Conversacion libre',
            ]);

            return response()->json($conversacion, 201);

        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Error creando conversacion', ['mensaje' => $e->getMessage()]);
            return response()->json(['message' => 'Error al crear la conversacion'], 500);
        }
    }

    /**
     * Lista las conversaciones del estudiante autenticado, mas recientes primero.
     * Con ?tipo=libre o ?tipo=guiada trae solo las de ese tipo: el panel del chat lo usa para
     * saber cuantas se borrarian (campo "total" del paginador) antes de pedir confirmacion.
     */
    public function index(Request $request)
    {
        try {
            $request->validate([
                'tipo' => 'nullable|in:guiada,libre',
            ]);

            $conversaciones = ConversacionIa::where('estudiante_id', Auth::id())
                ->when($request->filled('tipo'), fn ($consulta) => $consulta->where('tipo', $request->tipo))
                ->orderBy('updated_at', 'desc')
                ->paginate(15);

            return response()->json($conversaciones, 200);

        } catch (ValidationException $e) {
            throw $e; // Laravel responde 422 con el detalle del campo
        } catch (\Exception $e) {
            Log::error('Error listando conversaciones', ['mensaje' => $e->getMessage()]);
            return response()->json(['message' => 'Error al obtener las conversaciones'], 500);
        }
    }

    /**
     * Obtiene una conversacion con su historial completo de mensajes.
     */
    public function show(int $id)
    {
        try {
            $conversacion = ConversacionIa::with('mensajes')->findOrFail($id);
            $this->verificarPropietario($conversacion);

            return response()->json($conversacion, 200);

        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Error obteniendo conversacion', ['mensaje' => $e->getMessage()]);
            return response()->json(['message' => 'Error al obtener la conversacion'], 500);
        }
    }

    /**
     * Recibe un mensaje del estudiante, elige la estrategia (guiada o libre)
     * segun el tipo de la conversacion, y guarda ambos mensajes (usuario e IA).
     */
    public function enviarMensaje(Request $request, int $id)
    {
        try {
            $request->validate([
                'contenido' => 'required|string|max:2000',
            ]);

            $conversacion = ConversacionIa::findOrFail($id);
            $this->verificarPropietario($conversacion);

            // Primero se pide la respuesta y solo después se guarda. Si la IA no responde, no
            // queda en el historial ni la pregunta sin respuesta ni un falso mensaje de la IA, y
            // el reintento del frontend no duplica la pregunta. Además, la pregunta actual no se
            // cuela dos veces en el prompt (como historial y como pregunta)
            $estrategia = $this->resolverEstrategia($conversacion->tipo);
            $textoRespuesta = $estrategia->generarRespuesta($conversacion, $request->contenido);

            // Los dos mensajes se guardan juntos: o quedan ambos o ninguno
            [$mensajeUsuario, $mensajeIa] = DB::transaction(function () use ($conversacion, $request, $textoRespuesta) {
                $mensajeUsuario = MensajeIa::create([
                    'conversacion_id' => $conversacion->id,
                    'emisor'          => 'usuario',
                    'contenido'       => $request->contenido,
                ]);

                $mensajeIa = MensajeIa::create([
                    'conversacion_id' => $conversacion->id,
                    'emisor'          => 'ia',
                    'contenido'       => $textoRespuesta,
                ]);

                $conversacion->touch();

                return [$mensajeUsuario, $mensajeIa];
            });

            return response()->json([
                'mensaje_usuario' => $mensajeUsuario,
                'respuesta_ia'    => $mensajeIa,
            ], 200);

        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (IaNoDisponibleException $e) {
            // Ningún modelo de Gemini respondió a tiempo (Google saturado o sin cuota)
            return response()->json([
                'message' => 'La IA está muy ocupada en este momento. Intenta de nuevo en unos segundos.',
            ], 503);
        } catch (\Exception $e) {
            Log::error('Error enviando mensaje de chat', ['mensaje' => $e->getMessage()]);
            return response()->json(['message' => 'Error al procesar el mensaje'], 500);
        }
    }

    /**
     * Elimina una conversacion, solo si pertenece al estudiante autenticado.
     */
    public function destroy(int $id)
    {
        try {
            $conversacion = ConversacionIa::findOrFail($id);
            $this->verificarPropietario($conversacion);

            $conversacion->delete();

            return response()->json(['message' => 'Conversacion eliminada'], 200);

        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Error eliminando conversacion', ['mensaje' => $e->getMessage()]);
            return response()->json(['message' => 'Error al eliminar la conversacion'], 500);
        }
    }

    /**
     * Borra todo el historial de chats del estudiante autenticado. Con "tipo" (libre o guiada)
     * borra solo las de ese tipo; sin el, todas. Nunca toca las de otro usuario, porque el filtro
     * por estudiante_id va siempre. Los mensajes se borran solos por la llave foranea con cascade
     * de mensajes_ia. Responde cuantas conversaciones se borraron.
     */
    public function destroyTodas(Request $request)
    {
        try {
            $request->validate([
                'tipo' => 'nullable|in:guiada,libre',
            ]);

            // Un solo DELETE con WHERE (Query Builder de Eloquent, sin SQL concatenado)
            $eliminadas = ConversacionIa::where('estudiante_id', Auth::id())
                ->when($request->filled('tipo'), fn ($consulta) => $consulta->where('tipo', $request->tipo))
                ->delete();

            return response()->json([
                'message'    => $eliminadas === 1 ? 'Se borro 1 conversacion' : "Se borraron {$eliminadas} conversaciones",
                'eliminadas' => $eliminadas,
            ], 200);

        } catch (ValidationException $e) {
            throw $e; // Laravel responde 422 con el detalle del campo
        } catch (\Exception $e) {
            Log::error('Error borrando el historial de conversaciones', ['mensaje' => $e->getMessage()]);
            return response()->json(['message' => 'Error al borrar el historial'], 500);
        }
    }

    /**
     * Patron Strategy en accion: el controlador NUNCA decide como se restringe
     * el contenido, solo elige QUE estrategia usar segun el tipo guardado en BD.
     */
    private function resolverEstrategia(string $tipo): ChatStrategy
    {
        return match ($tipo) {
            'guiada' => new GuiadaStrategy(),
            'libre'  => new LibreStrategy(),
        };
    }

    /**
     * Verifica que la conversacion pertenece al estudiante autenticado.
     * Distinto de verificarAccesoAGuia: aqui se protege la conversacion en si,
     * no la guia (eso ya se valida una sola vez, al crear la conversacion).
     */
    private function verificarPropietario(ConversacionIa $conversacion): void
    {
        if ($conversacion->estudiante_id !== Auth::id()) {
            throw new AuthorizationException('No tienes acceso a esta conversacion.');
        }
    }
}