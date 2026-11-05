<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsEstudiante
{
    /**
     * Autorizar únicamente a usuarios autenticados con rol estudiante.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        if ($user->rol !== 'estudiante') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para esta acción',
            ], 403);
        }

        return $next($request);
    }
}
