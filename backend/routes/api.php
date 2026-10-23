<?php

use App\Http\Controllers\Api\ConversacionIaController;
use App\Http\Controllers\Api\GrupoController;
use App\Http\Controllers\Api\GuiaController;
use App\Http\Controllers\Api\MiniProyectoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas (Autenticación)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (auth:sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    });

    Route::middleware('EsEstudiante')
        ->prefix('usuario')
        ->group(function () {
            Route::get('/modo', [UserController::class, 'showMode']);
            Route::patch('/modo', [UserController::class, 'updateMode']);
        });

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Módulo de IA
    Route::get('/conversaciones', [ConversacionIaController::class, 'index']);
    Route::post('/conversaciones', [ConversacionIaController::class, 'store']);
    Route::get('/conversaciones/{id}', [ConversacionIaController::class, 'show']);
    Route::post('/conversaciones/{id}/mensajes', [ConversacionIaController::class, 'enviarMensaje']);
    Route::delete('/conversaciones/{id}', [ConversacionIaController::class, 'destroy']);

    // Grupos
    Route::get('/grupos', [GrupoController::class, 'index']);
    Route::post('/grupos', [GrupoController::class, 'store']);
    Route::get('/grupos/{id}', [GrupoController::class, 'show']);
    Route::post('/grupos/unirse', [GrupoController::class, 'unirse']);

    // Guías de Estudio
    Route::get('/guias', [GuiaController::class, 'index']);
    Route::post('/guias', [GuiaController::class, 'store']);
    Route::get('/guias/{id}', [GuiaController::class, 'show']);

    // Mini Proyectos
    Route::get('/mini-proyectos', [MiniProyectoController::class, 'index']);
    Route::post('/mini-proyectos', [MiniProyectoController::class, 'store']);
    Route::get('/mini-proyectos/{id}', [MiniProyectoController::class, 'show']);
});
