<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Log;

/**
 * Verifica el ID token que entrega el botón "Continuar con Google".
 * Usa la librería oficial de Google, que revisa la firma, el emisor,
 * el vencimiento y que el token sea para nuestro Client ID.
 * Está aparte para poder reemplazarla en las pruebas.
 */
class GoogleIdTokenVerifier
{
    /**
     * Devuelve los datos de la cuenta si el token es válido, o null si no lo es.
     *
     * @return array{sub: string, email: string, name: string, email_verified: bool}|null
     */
    public function verificar(string $idToken, string $clientId): ?array
    {
        try {
            $datos = (new Client(['client_id' => $clientId]))->verifyIdToken($idToken);
        } catch (\Throwable $e) {
            // Token mal formado, firma inválida o claves de Google inalcanzables
            Log::warning('No se pudo verificar el token de Google', ['mensaje' => $e->getMessage()]);

            return null;
        }

        if (! is_array($datos) || empty($datos['sub']) || empty($datos['email'])) {
            return null;
        }

        return [
            'sub'            => (string) $datos['sub'],
            'email'          => mb_strtolower((string) $datos['email']),
            // Algunas cuentas no tienen nombre público: se usa la parte antes de la @
            'name'           => (string) ($datos['name'] ?? strstr($datos['email'], '@', true)),
            'email_verified' => filter_var($datos['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
