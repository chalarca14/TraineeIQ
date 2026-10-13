<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ConversacionIaController;
use App\Http\Controllers\Api\GrupoController;
use App\Http\Controllers\Api\GuiaController;
use App\Http\Controllers\Api\MiniProyectoController;

/*

| API Routes

| Aquí es donde puedes registrar las rutas de la API para tu aplicación. Estas
| rutas son cargadas por el RouteServiceProvider y todas ellas se
| asignarán al grupo de middleware "api".
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas protegidas para el módulo de IA (Documento Maestro v3)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/conversaciones', [ConversacionIaController::class, 'index']);
    Route::post('/conversaciones', [ConversacionIaController::class, 'store']);
    Route::get('/conversaciones/{id}', [ConversacionIaController::class, 'show']);
    Route::post('/conversaciones/{id}/mensajes', [ConversacionIaController::class, 'enviarMensaje']);
    Route::delete('/conversaciones/{id}', [ConversacionIaController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Rutas de Grupos
    Route::get('/grupos', [GrupoController::class, 'index']);
    Route::post('/grupos', [GrupoController::class, 'store']);
    Route::get('/grupos/{id}', [GrupoController::class, 'show']);
    Route::post('/grupos/unirse', [GrupoController::class, 'unirse']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Rutas de Guías de Estudio
    Route::get('/guias', [GuiaController::class, 'index']);
    Route::post('/guias', [GuiaController::class, 'store']);
    Route::get('/guias/{id}', [GuiaController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Rutas de Mini Proyectos
    Route::get('/mini-proyectos', [MiniProyectoController::class, 'index']);
    Route::post('/mini-proyectos', [MiniProyectoController::class, 'store']);
    Route::get('/mini-proyectos/{id}', [MiniProyectoController::class, 'show']);
});