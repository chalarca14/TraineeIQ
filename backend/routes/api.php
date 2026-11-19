<?php

use App\Http\Controllers\Api\EntregaMiniProyectoController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\GrupoController;
use App\Http\Controllers\Api\GuiaController;
use App\Http\Controllers\Api\GuiaSemanaController;
use App\Http\Controllers\Api\MaterialSemanaController;
use App\Http\Controllers\Api\MiniProyectoController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatIaController;
use App\Http\Controllers\RecuperarPasswordController;
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

// "Continuar con Google": entrar y, si la cuenta es nueva, completar el registro
Route::post('/auth/google', [GoogleAuthController::class, 'entrar'])->middleware('throttle:10,1');
Route::post('/auth/google/completar', [GoogleAuthController::class, 'completarRegistro'])->middleware('throttle:10,1');

// "¿Olvidaste tu contraseña?": pedir el enlace por correo y crear la contraseña nueva con él
Route::post('/password/olvide', [RecuperarPasswordController::class, 'enviarEnlace'])->middleware('throttle:5,1');
Route::post('/password/restablecer', [RecuperarPasswordController::class, 'restablecer'])->middleware('throttle:5,1');

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
            Route::get('/xp', [UserController::class, 'experiencia']);
        });

    // Cambiar la contraseña: sirve para ambos roles y tiene limite de intentos
    Route::patch('/usuario/password', [UserController::class, 'updatePassword'])->middleware('throttle:5,1');

    // Cambiar el nombre (solo el nombre: el correo no se edita). Ambos roles
    Route::patch('/usuario/perfil', [UserController::class, 'updateProfile'])->middleware('throttle:10,1');

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Chat con IA: guiado y libre (Integrante 1)
    Route::get('/conversaciones', [ChatIaController::class, 'index']);
    Route::post('/conversaciones', [ChatIaController::class, 'store']);
    Route::get('/conversaciones/{id}', [ChatIaController::class, 'show']);
    Route::post('/conversaciones/{id}/mensajes', [ChatIaController::class, 'enviarMensaje']);
    // Borrar todo el historial del usuario (opcional ?tipo=libre o ?tipo=guiada)
    Route::delete('/conversaciones', [ChatIaController::class, 'destroyTodas']);
    Route::delete('/conversaciones/{id}', [ChatIaController::class, 'destroy']);

    // Grupos
    Route::get('/grupos', [GrupoController::class, 'index']);
    Route::post('/grupos', [GrupoController::class, 'store'])->middleware('EsInstructor');
    Route::get('/grupos/{id}', [GrupoController::class, 'show']);
    Route::patch('/grupos/{id}', [GrupoController::class, 'update'])->whereNumber('id')->middleware('EsInstructor');
    Route::delete('/grupos/{id}', [GrupoController::class, 'destroy'])->whereNumber('id')->middleware('EsInstructor');
    Route::patch('/grupos/{id}/semana-actual', [GrupoController::class, 'actualizarSemanaActual'])->middleware('EsInstructor');
    Route::get('/grupos/{id}/entregas', [GrupoController::class, 'entregas'])->whereNumber('id')->middleware('EsInstructor');
    // Limite de intentos: evita probar codigos de acceso hasta acertar uno
    Route::post('/grupos/unirse', [GrupoController::class, 'unirse'])->middleware(['EsEstudiante', 'throttle:10,1']);

    // Guías de Estudio
    Route::get('/guias', [GuiaController::class, 'index']);
    Route::post('/guias', [GuiaController::class, 'store']);
    Route::get('/guias/{id}', [GuiaController::class, 'show']);
    Route::put('/guias/{id}', [GuiaController::class, 'update']);
    // Borrar: ambos roles entran, pero GuiaPolicy::delete deja solo al dueño (estudiante en las
    // personales, instructor del grupo en las de grupo)
    Route::delete('/guias/{id}', [GuiaController::class, 'destroy'])->whereNumber('id');
    Route::get('/guias/{guiaId}/semanas', [GuiaSemanaController::class, 'index'])->whereNumber('guiaId');
    Route::get('/guias/{guiaId}/texto', [GuiaSemanaController::class, 'texto'])->whereNumber('guiaId');
    Route::get('/guias/{guiaId}/semanas/{numero}', [GuiaSemanaController::class, 'show'])
        ->whereNumber(['guiaId', 'numero']);
    Route::post('/guias/{guiaId}/semanas/{numero}/generar', [GuiaSemanaController::class, 'generar'])
        ->whereNumber(['guiaId', 'numero']);

    // Guías de la semana: guías detalladas que el instructor adjunta a una semana. Ver y listar,
    // quien lee la semana; agregar y quitar, solo el instructor del grupo (GuiaPolicy)
    Route::get('/guias/{guiaId}/semanas/{numero}/materiales', [MaterialSemanaController::class, 'index'])
        ->whereNumber(['guiaId', 'numero']);
    Route::post('/guias/{guiaId}/semanas/{numero}/materiales', [MaterialSemanaController::class, 'store'])
        ->whereNumber(['guiaId', 'numero'])->middleware('throttle:20,1');
    Route::delete('/guias/{guiaId}/semanas/{numero}/materiales/{id}', [MaterialSemanaController::class, 'destroy'])
        ->whereNumber(['guiaId', 'numero', 'id']);

    // Mini Proyectos: los genera la IA por semana, aqui solo se leen
    Route::get('/mini-proyectos/{id}', [MiniProyectoController::class, 'show'])->whereNumber('id');
    Route::get('/guias/{guiaId}/mini-proyectos', [MiniProyectoController::class, 'porGuia'])->whereNumber('guiaId');

    // Entrega del repositorio: es por estudiante
    Route::middleware('EsEstudiante')->whereNumber('id')->group(function () {
        Route::get('/mini-proyectos/{id}/entrega', [EntregaMiniProyectoController::class, 'show']);
        Route::put('/mini-proyectos/{id}/entrega', [EntregaMiniProyectoController::class, 'guardar']);
        Route::post('/mini-proyectos/{id}/entrega/evaluar', [EntregaMiniProyectoController::class, 'evaluar']);
    });

    // Notificaciones de la campanita: hoy solo las recibe el instructor
    // Campanita de avisos, para instructores y estudiantes. Cada uno solo ve los suyos:
    // la lista filtra por usuario_id y marcar como leída pasa por NotificacionPolicy
    Route::prefix('notificaciones')->group(function () {
        Route::get('/', [NotificacionController::class, 'index']);
        Route::patch('/leidas', [NotificacionController::class, 'marcarTodas']);
        Route::patch('/{id}/leida', [NotificacionController::class, 'marcarLeida'])->whereNumber('id');
    });
});
