<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Registra los alias de los middlewares de rol para poder usarlos
        // como string dentro de Route::middleware('EsEstudiante') etc.
        $middleware->alias([
            'EsInstructor'  => \App\Http\Middleware\EsInstructor::class,
            'EsEstudiante'  => \App\Http\Middleware\EsEstudiante::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();