<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController; // Asegúrate de ajustar este namespace según tu proyecto
use App\Http\Controllers\ChatIaController;
use App\Http\Controllers\RecomendacionController;
use App\Http\Controllers\MiniProyectoController as MiniProyectoIaController;
use App\Http\Controllers\Api\GrupoController;
use App\Http\Controllers\Api\GuiaController;
use App\Http\Controllers\Api\MiniProyectoController;

/*
|--------------------------------------------------------------------------
| Rutas Públicas (Autenticación)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (auth:sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Chat con IA: guiado y libre (Integrante 1)
    Route::get('/conversaciones', [ChatIaController::class, 'index']);
    Route::post('/conversaciones', [ChatIaController::class, 'store']);
    Route::get('/conversaciones/{id}', [ChatIaController::class, 'show']);
    Route::post('/conversaciones/{id}/mensajes', [ChatIaController::class, 'enviarMensaje']);
    Route::delete('/conversaciones/{id}', [ChatIaController::class, 'destroy']);

    // Recomendaciones y mini proyectos generados por IA (Integrante 1)
    Route::post('/recomendaciones', [RecomendacionController::class, 'generarRecomendacion']);
    Route::get('/recomendaciones/historial', [RecomendacionController::class, 'historial']);
    Route::post('/mini-proyectos/generar', [MiniProyectoIaController::class, 'generarMiniProyecto']);
    Route::get('/mini-proyectos/guia/{guiaId}', [MiniProyectoIaController::class, 'porGuia']);

    // Grupos
    Route::get('/grupos', [GrupoController::class, 'index']);
    Route::post('/grupos', [GrupoController::class, 'store']);
    Route::get('/grupos/{id}', [GrupoController::class, 'show']);
    Route::post('/grupos/unirse', [GrupoController::class, 'unirse']);

    // Guías de Estudio
    Route::get('/guias', [GuiaController::class, 'index']);
    Route::post('/guias', [GuiaController::class, 'store']);
    Route::get('/guias/{id}', [GuiaController::class, 'show']);

    // Mini Proyectos (CRUD manual de Integrante 3)
    Route::get('/mini-proyectos', [MiniProyectoController::class, 'index']);
    Route::post('/mini-proyectos', [MiniProyectoController::class, 'store']);
    Route::get('/mini-proyectos/{id}', [MiniProyectoController::class, 'show']);
});