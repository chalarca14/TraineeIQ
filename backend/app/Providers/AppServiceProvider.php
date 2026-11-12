<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Correo de "¿Olvidaste tu contraseña?": en español y con el enlace al FRONTEND (/restablecer),
        // no a una ruta web de Laravel, porque las pantallas las pinta Vue.
        ResetPassword::toMailUsing(function ($usuario, string $token) {
            $enlace = config('app.frontend_url') . '/restablecer?' . http_build_query([
                'token' => $token,
                'email' => $usuario->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Recupera tu contraseña de TraineeIQ')
                ->greeting("Hola, {$usuario->name}")
                ->line('Recibimos una solicitud para crear una nueva contraseña para tu cuenta de TraineeIQ.')
                ->action('Crear nueva contraseña', $enlace)
                ->line('El enlace vence en 60 minutos.')
                ->line('Si no lo pediste, ignora este correo: tu contraseña no cambiará.')
                ->salutation('El equipo de TraineeIQ');
        });
    }
}
