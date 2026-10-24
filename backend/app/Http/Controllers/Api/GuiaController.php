<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guia;
use App\Models\Grupo;
use App\Services\PdfTextExtractor;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Exception;

class GuiaController extends Controller
{
    /**
     * Listar guías con acceso autorizado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->rol === 'instructor') {
            // El instructor ve las guías de sus grupos
            $gruposIds = Grupo::where('profesor_id', $user->id)->pluck('id');
            $guias = Guia::whereIn('grupo_id', $gruposIds)
                ->with(['temas' => fn($q) => $q->orderBy('orden', 'asc')])
                ->paginate(15);
        } else {
            // El estudiante ve sus guías personales y las de los grupos donde está inscrito
            $gruposInscritosIds = $user->gruposInscritos()->pluck('grupos.id');
            
            $guias = Guia::where(function ($query) use ($user, $gruposInscritosIds) {
                    $query->where('usuario_id', $user->id)
                          ->orWhereIn('grupo_id', $gruposInscritosIds);
                })
                ->with(['temas' => fn($q) => $q->orderBy('orden', 'asc')])
                ->paginate(15);
        }

        return response()->json($guias, 200);
    }

    /**
     * Crear una nueva guía procesando un PDF con IA.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Validaciones
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'archivo' => 'required|file|mimes:pdf|max:10240',
            'grupo_id' => 'nullable|integer|exists:grupos,id',
            'semanas_totales' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Determinación de origen y pertenencia de grupo según el rol
        $origen = null;
        $grupoId = null;
        $usuarioId = null;

        if ($user->rol === 'instructor') {
            if (!$request->filled('grupo_id')) {
                return response()->json(['message' => 'El instructor debe especificar un grupo_id.'], 403);
            }

            $grupo = Grupo::find($request->grupo_id);
            if (!$grupo || $grupo->profesor_id !== $user->id) {
                return response()->json(['message' => 'El grupo especificado no pertenece al instructor.'], 403);
            }

            $origen = 'grupo';
            $grupoId = $grupo->id;
        } else {
            $origen = 'personal';
            $usuarioId = $user->id;
        }

        // 2. Guardar el archivo temporalmente
        $path = $request->file('archivo')->store('guias', 'public');

        try {
            // 3. Extracción de texto del PDF
            $fullPath = Storage::disk('public')->path($path);
            $extractor = new PdfTextExtractor();
            $textoExtraido = $extractor->extraer($fullPath);

            if (empty(trim($textoExtraido))) {
                Storage::disk('public')->delete($path);
                return response()->json([
                    'message' => 'El PDF no tiene texto legible. Sube un PDF que no sea una imagen escaneada.'
                ], 422);
            }

            // 4. Segmentación con Gemini
            $gemini = GeminiService::obtenerInstancia();
            $temasSegmentados = $gemini->segmentarGuiaEnTemas($textoExtraido);

        } catch (Exception $e) {
            Log::error('Error procesando el PDF de la guía', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile() . ':' . $e->getLine(),
            ]);
            Storage::disk('public')->delete($path);

            if (str_contains($e->getMessage(), '503') || str_contains(strtolower($e->getMessage()), 'busy')) {
                return response()->json(['message' => 'La IA está ocupada, intenta de nuevo en un momento.'], 503);
            }

            return response()->json(['message' => 'Error al crear la guía'], 500);
        }

        // 5. Transacción de creación en base de datos
        try {
            $guia = DB::transaction(function () use ($request, $origen, $grupoId, $usuarioId, $temasSegmentados, $path) {
                $nuevaGuia = Guia::create([
                    'nombre' => $request->nombre,
                    'origen' => $origen,
                    'grupo_id' => $grupoId,
                    'usuario_id' => $usuarioId,
                    'archivo_pdf' => $path,
                    'semanas_totales' => $request->input('semanas_totales', 1),
                ]);

                foreach ($temasSegmentados as $index => $tema) {
                    $nuevaGuia->temas()->create([
                        'nombre_tema' => $tema['nombre_tema'],
                        'descripcion' => $tema['descripcion'],
                        'orden' => $index + 1,
                    ]);
                }

                return $nuevaGuia;
            });

            $guia->load(['temas' => fn($q) => $q->orderBy('orden', 'asc')]);

            return response()->json([
                'message' => 'Guía creada correctamente',
                'guia' => $guia
            ], 201);

        } catch (Exception $e) {
            Log::error('Error guardando la guía en la base de datos', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile() . ':' . $e->getLine(),
            ]);
            Storage::disk('public')->delete($path);
            return response()->json(['message' => 'Error al crear la guía'], 500);
        }
    }

    /**
     * Ver el detalle de una guía.
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $guia = Guia::with(['temas' => fn($q) => $q->orderBy('orden', 'asc')])->find($id);

        if (!$guia) {
            return response()->json(['message' => 'Guía no encontrada'], 404);
        }

        if ($user->rol === 'instructor') {
            $grupo = Grupo::find($guia->grupo_id);
            if (!$grupo || $grupo->profesor_id !== $user->id) {
                return response()->json(['message' => 'No tienes acceso a esta guía.'], 403);
            }
        } else {
            $gruposInscritosIds = $user->gruposInscritos()->pluck('grupos.id')->toArray();
            
            $tieneAcceso = ($guia->usuario_id === $user->id) || 
                        ($guia->grupo_id && in_array($guia->grupo_id, $gruposInscritosIds));

            if (!$tieneAcceso) {
                return response()->json(['message' => 'No tienes acceso a esta guía.'], 403);
            }
        }

        return response()->json(['guia' => $guia], 200);
    }
}