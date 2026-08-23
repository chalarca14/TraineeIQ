<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuiaController;
use App\Http\Controllers\ChatController;

Route::middleware('auth:sanctum')->group(function () {
    // ... rutas previas de Auth y Guías ...

    // Rutas del Chat de IA
    Route::get('/chat/conversaciones', [ChatController::class, 'index']);
    Route::post('/chat/conversaciones', [ChatController::class, 'storeConversacion']);
    Route::get('/chat/conversaciones/{id}', [ChatController::class, 'show']);
    Route::post('/chat/conversaciones/{id}/mensajes', [ChatController::class, 'enviarMensaje']);
});

// Rutas Públicas

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas Protegidas (Requieren Token Bearer)

Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Guías de Aprendizaje
    Route::get('/guias', [GuiaController::class, 'index']);
    Route::post('/guias', [GuiaController::class, 'store']);
    Route::get('/guias/{id}', [GuiaController::class, 'show']);

});