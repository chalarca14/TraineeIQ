<?php

namespace App\Services;

use App\Exceptions\AccesoGoogleException;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

/**
 * Lógica de "Continuar con Google".
 * Un usuario que ya existe entra directo. Uno nuevo recibe un registro_token y
 * termina su registro eligiendo rol y contraseña: la cuenta no se crea antes,
 * así que toda cuenta tiene contraseña y puede usar también el login normal.
 */
class GoogleAuthService
{
    // Cómo se muestra cada rol en los mensajes
    private const ROLES = ['instructor' => 'instructor', 'estudiante' => 'aprendiz'];

    /**
     * Recibe el verificador del token (en las pruebas se reemplaza por uno simulado).
     */
    public function __construct(private readonly GoogleIdTokenVerifier $verificador)
    {
    }

    /**
     * Verifica la cuenta de Google y decide si el usuario entra o debe completar su registro.
     *
     * @return array{usuario: User, token: string}|array{requiere_registro: true, registro_token: string, nombre: string, email: string}
     */
    public function entrar(string $credencial, string $rol): array
    {
        $cuenta = $this->verificarCuenta($credencial);

        // Primero por google_id; si nunca entró con Google, por su correo
        $usuario = User::where('google_id', $cuenta['sub'])->first()
            ?? User::where('email', $cuenta['email'])->first();

        if ($usuario === null) {
            return [
                'requiere_registro' => true,
                'registro_token'    => $this->crearRegistroToken($cuenta),
                'nombre'            => $cuenta['name'],
                'email'             => $cuenta['email'],
            ];
        }

        // Misma regla que el login normal: la pestaña elegida debe ser el rol de la cuenta
        if ($usuario->rol !== $rol) {
            throw new AccesoGoogleException(
                'Esta cuenta está registrada como ' . self::ROLES[$usuario->rol] . '. Elige esa pestaña para entrar.',
                403
            );
        }

        $this->vincular($usuario, $cuenta['sub']);

        return ['usuario' => $usuario, 'token' => $usuario->createToken('auth_token')->plainTextToken];
    }

    /**
     * Crea la cuenta de un usuario nuevo de Google con el rol y la contraseña que eligió.
     *
     * @return array{usuario: User, token: string}
     */
    public function completarRegistro(string $registroToken, string $nombre, string $rol, string $password): array
    {
        $cuenta = $this->leerRegistroToken($registroToken);

        if (User::where('email', $cuenta['email'])->orWhere('google_id', $cuenta['sub'])->exists()) {
            throw new AccesoGoogleException('Este correo ya tiene una cuenta. Inicia sesión con Google.', 409);
        }

        try {
            $usuario = User::create([
                'name'      => $nombre,
                'email'     => $cuenta['email'],
                'google_id' => $cuenta['sub'],
                'password'  => Hash::make($password),
                'rol'       => $rol,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Dos clics a la vez: la otra petición ya creó la cuenta
            throw new AccesoGoogleException('Este correo ya tiene una cuenta. Inicia sesión con Google.', 409);
        }

        // Google ya verificó el correo; no está en $fillable porque solo lo asigna el backend
        $usuario->forceFill(['email_verified_at' => now()])->save();

        return ['usuario' => $usuario, 'token' => $usuario->createToken('auth_token')->plainTextToken];
    }

    /**
     * Verifica el token de Google y exige un correo verificado por Google.
     *
     * @return array{sub: string, email: string, name: string, email_verified: bool}
     */
    private function verificarCuenta(string $credencial): array
    {
        $clientId = config('services.google.client_id');

        if (empty($clientId)) {
            throw new AccesoGoogleException('El inicio de sesión con Google no está disponible por ahora.', 503);
        }

        $cuenta = $this->verificador->verificar($credencial, $clientId);

        if ($cuenta === null) {
            throw new AccesoGoogleException('No pudimos verificar tu cuenta de Google. Intenta de nuevo.', 401);
        }

        // Sin correo verificado no se puede vincular con seguridad a una cuenta existente
        if (! $cuenta['email_verified']) {
            throw new AccesoGoogleException('Tu cuenta de Google no tiene el correo verificado.', 401);
        }

        return $cuenta;
    }

    /**
     * Guarda el google_id y marca el correo como verificado si aún no lo estaban.
     */
    private function vincular(User $usuario, string $googleId): void
    {
        $cambios = [];

        if ($usuario->google_id === null) {
            $cambios['google_id'] = $googleId;
        }

        if ($usuario->email_verified_at === null) {
            $cambios['email_verified_at'] = now();
        }

        if ($cambios !== []) {
            $usuario->forceFill($cambios)->save();
        }
    }

    /**
     * Cifra los datos de la cuenta de Google con la APP_KEY y una fecha de vencimiento.
     * El frontend lo devuelve al completar el registro; no se guarda en ninguna tabla.
     */
    private function crearRegistroToken(array $cuenta): string
    {
        return Crypt::encryptString(json_encode([
            'sub'    => $cuenta['sub'],
            'email'  => $cuenta['email'],
            'expira' => now()->addMinutes(config('services.google.minutos_registro'))->getTimestamp(),
        ]));
    }

    /**
     * Descifra el registro_token y revisa que no esté vencido ni alterado.
     *
     * @return array{sub: string, email: string}
     */
    private function leerRegistroToken(string $registroToken): array
    {
        try {
            $datos = json_decode(Crypt::decryptString($registroToken), true);
        } catch (DecryptException $e) {
            $datos = null;
        }

        if (! is_array($datos) || empty($datos['sub']) || empty($datos['email']) || ($datos['expira'] ?? 0) < now()->getTimestamp()) {
            throw new AccesoGoogleException('El tiempo para completar el registro terminó. Vuelve a intentarlo con Google.', 422);
        }

        return $datos;
    }
}
