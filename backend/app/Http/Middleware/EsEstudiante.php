<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsEstudiante
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'No has iniciado sesión.',
            ], 401);
        }

        if ($request->user()->rol !== 'estudiante') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para acceder a este recurso.',
            ], 403);
        }

        return $next($request);
    }
}
