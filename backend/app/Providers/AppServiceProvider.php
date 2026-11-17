<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

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
        // Mailer "brevo": envía los correos por la API HTTP de Brevo y no por SMTP, porque
        // Render gratis bloquea los puertos de SMTP. Es el ejemplo de la documentación de Laravel
        // ("Additional Symfony Transports"). La clave sale de config/services.php, nunca del código
        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory)->create(
                new Dsn('brevo+api', 'default', config('services.brevo.key'))
            );
        });

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
