<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RecomendacionController;
use App\Http\Controllers\MiniProyectoController;
use App\Http\Controllers\ChatIaController;

// --- Rutas publicas de autenticacion ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --- Rutas protegidas: requieren token valido de Sanctum ---
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Rutas protegidas ademas por rol Estudiante ---
    Route::middleware('EsEstudiante')->group(function () {

        // Recomendaciones (Integrante 1)
        Route::post('/recomendaciones', [RecomendacionController::class, 'generarRecomendacion']);
        Route::get('/recomendaciones/historial', [RecomendacionController::class, 'historial']);

        // Mini proyectos (Integrante 1)
        Route::post('/mini-proyectos', [MiniProyectoController::class, 'generarMiniProyecto']);
        Route::get('/mini-proyectos/guia/{guiaId}', [MiniProyectoController::class, 'porGuia']);

        // Chat con IA: guiado y libre (Integrante 1)
        Route::post('/conversaciones', [ChatIaController::class, 'store']);
        Route::get('/conversaciones', [ChatIaController::class, 'index']);
        Route::get('/conversaciones/{id}', [ChatIaController::class, 'show']);
        Route::post('/conversaciones/{id}/mensajes', [ChatIaController::class, 'enviarMensaje']);
        Route::delete('/conversaciones/{id}', [ChatIaController::class, 'destroy']);
    });
});