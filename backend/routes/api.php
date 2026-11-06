<?php

use App\Http\Controllers\Api\EntregaMiniProyectoController;
use App\Http\Controllers\Api\GrupoController;
use App\Http\Controllers\Api\GuiaController;
use App\Http\Controllers\Api\GuiaSemanaController;
use App\Http\Controllers\Api\MiniProyectoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatIaController;
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

    // Chat con IA: guiado y libre (Integrante 1)
    Route::get('/conversaciones', [ChatIaController::class, 'index']);
    Route::post('/conversaciones', [ChatIaController::class, 'store']);
    Route::get('/conversaciones/{id}', [ChatIaController::class, 'show']);
    Route::post('/conversaciones/{id}/mensajes', [ChatIaController::class, 'enviarMensaje']);
    Route::delete('/conversaciones/{id}', [ChatIaController::class, 'destroy']);

    // Grupos
    Route::get('/grupos', [GrupoController::class, 'index']);
    Route::post('/grupos', [GrupoController::class, 'store'])->middleware('EsInstructor');
    Route::get('/grupos/{id}', [GrupoController::class, 'show']);
    Route::patch('/grupos/{id}/semana-actual', [GrupoController::class, 'actualizarSemanaActual'])->middleware('EsInstructor');
    Route::post('/grupos/unirse', [GrupoController::class, 'unirse'])->middleware('EsEstudiante');

    // Guías de Estudio
    Route::get('/guias', [GuiaController::class, 'index']);
    Route::post('/guias', [GuiaController::class, 'store']);
    Route::get('/guias/{id}', [GuiaController::class, 'show']);
    Route::put('/guias/{id}', [GuiaController::class, 'update']);
    Route::get('/guias/{guiaId}/semanas', [GuiaSemanaController::class, 'index'])->whereNumber('guiaId');
    Route::get('/guias/{guiaId}/semanas/{numero}', [GuiaSemanaController::class, 'show'])
        ->whereNumber(['guiaId', 'numero']);
    Route::post('/guias/{guiaId}/semanas/{numero}/generar', [GuiaSemanaController::class, 'generar'])
        ->whereNumber(['guiaId', 'numero']);

    // Mini Proyectos: los genera la IA por semana, aqui solo se leen
    Route::get('/mini-proyectos/{id}', [MiniProyectoController::class, 'show'])->whereNumber('id');

    // Entrega del repositorio: es por estudiante
    Route::middleware('EsEstudiante')->whereNumber('id')->group(function () {
        Route::get('/mini-proyectos/{id}/entrega', [EntregaMiniProyectoController::class, 'show']);
        Route::put('/mini-proyectos/{id}/entrega', [EntregaMiniProyectoController::class, 'guardar']);
        Route::post('/mini-proyectos/{id}/entrega/evaluar', [EntregaMiniProyectoController::class, 'evaluar']);
    });
});
