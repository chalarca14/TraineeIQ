<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;
use Tests\TestCase;

/**
 * Mailer "brevo" de producción. El envío real no se prueba aquí: necesita la clave de Render.
 */
class CorreoBrevoTest extends TestCase
{
    /**
     * Con MAIL_MAILER=brevo, Laravel envía por la API HTTP de Brevo (no por SMTP, que Render bloquea).
     */
    public function test_el_mailer_brevo_usa_la_api_http(): void
    {
        config(['services.brevo.key' => 'clave-falsa-de-prueba']);

        $transporte = Mail::mailer('brevo')->getSymfonyTransport();

        $this->assertInstanceOf(BrevoApiTransport::class, $transporte);
        $this->assertStringStartsWith('brevo+api://', (string) $transporte);
    }
}
