<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GoogleIdTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * "Continuar con Google", con el verificador de Google simulado:
 * las pruebas nunca llaman a Google.
 */
class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    /** Cuenta de Google que devuelve el verificador simulado (null = token inválido). */
    private ?array $cuentaGoogle = [
        'sub'            => 'google-123',
        'email'          => 'ana@gmail.com',
        'name'           => 'Ana Pérez',
        'email_verified' => true,
    ];

    /**
     * Configura un Client ID de prueba y reemplaza el verificador de Google por uno simulado.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.client_id' => 'cliente-de-prueba.apps.googleusercontent.com']);

        $prueba = $this;
        $this->instance(GoogleIdTokenVerifier::class, new class($prueba) extends GoogleIdTokenVerifier {
            public function __construct(private GoogleAuthTest $prueba)
            {
            }

            public function verificar(string $idToken, string $clientId): ?array
            {
                return $this->prueba->cuentaSimulada();
            }
        });
    }

    /**
     * La cuenta que el verificador simulado devuelve en cada prueba.
     */
    public function cuentaSimulada(): ?array
    {
        return $this->cuentaGoogle;
    }

    /**
     * Una cuenta nueva no se crea todavía: recibe requiere_registro y un registro_token.
     */
    public function test_cuenta_nueva_pide_completar_el_registro(): void
    {
        $this->entrar('estudiante')
            ->assertOk()
            ->assertJsonPath('requiere_registro', true)
            ->assertJsonPath('nombre', 'Ana Pérez')
            ->assertJsonPath('email', 'ana@gmail.com')
            ->assertJsonStructure(['registro_token'])
            ->assertJsonMissingPath('token');

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Completar el registro crea la cuenta verificada, con google_id y contraseña,
     * y luego también sirve el login normal con esa contraseña.
     */
    public function test_completar_registro_crea_la_cuenta_verificada(): void
    {
        $registroToken = $this->entrar('estudiante')->json('registro_token');

        $this->completar($registroToken)
            ->assertCreated()
            ->assertJsonPath('user.email', 'ana@gmail.com')
            ->assertJsonPath('user.rol', 'instructor')
            ->assertJsonStructure(['token'])
            ->assertJsonMissingPath('user.google_id')
            ->assertJsonMissingPath('user.password');

        $usuario = User::where('email', 'ana@gmail.com')->firstOrFail();
        $this->assertSame('google-123', $usuario->google_id);
        $this->assertNotNull($usuario->email_verified_at);
        $this->assertSame('Ana P.', $usuario->name);
        $this->assertTrue(Hash::check('clave-segura', $usuario->password));

        $this->postJson('/api/login', ['email' => 'ana@gmail.com', 'password' => 'clave-segura', 'rol' => 'instructor'])
            ->assertOk();
    }

    /**
     * Quien ya tiene cuenta con correo y contraseña entra con Google y queda vinculado y verificado.
     */
    public function test_cuenta_existente_entra_y_queda_vinculada(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@gmail.com', 'rol' => 'estudiante', 'email_verified_at' => null]);

        $this->entrar('estudiante')
            ->assertOk()
            ->assertJsonPath('user.id', $usuario->id)
            ->assertJsonStructure(['token'])
            ->assertJsonMissingPath('requiere_registro');

        $usuario->refresh();
        $this->assertSame('google-123', $usuario->google_id);
        $this->assertNotNull($usuario->email_verified_at);
    }

    /**
     * Con la pestaña equivocada se rechaza, igual que el login normal.
     */
    public function test_pestana_de_rol_equivocada_responde_403(): void
    {
        User::factory()->create(['email' => 'ana@gmail.com', 'rol' => 'instructor']);

        $this->entrar('estudiante')
            ->assertForbidden()
            ->assertJsonPath('message', 'Esta cuenta está registrada como instructor. Elige esa pestaña para entrar.')
            ->assertJsonMissingPath('token');
    }

    /**
     * Un token que Google no valida responde 401.
     */
    public function test_token_invalido_responde_401(): void
    {
        $this->cuentaGoogle = null;

        $this->entrar('estudiante')->assertUnauthorized()->assertJsonMissingPath('token');
    }

    /**
     * Sin correo verificado por Google no se entra ni se vincula ninguna cuenta.
     */
    public function test_correo_no_verificado_por_google_responde_401(): void
    {
        $this->cuentaGoogle['email_verified'] = false;
        User::factory()->create(['email' => 'ana@gmail.com', 'rol' => 'estudiante']);

        $this->entrar('estudiante')->assertUnauthorized();
        $this->assertNull(User::where('email', 'ana@gmail.com')->value('google_id'));
    }

    /**
     * Sin GOOGLE_CLIENT_ID el servicio responde 503 en vez de fallar.
     */
    public function test_sin_client_id_responde_503(): void
    {
        config(['services.google.client_id' => null]);

        $this->entrar('estudiante')->assertStatus(503);
    }

    /**
     * Un registro_token alterado o vencido no crea la cuenta.
     */
    public function test_registro_token_alterado_o_vencido_responde_422(): void
    {
        $this->completar('token-inventado')->assertUnprocessable();

        $vencido = Crypt::encryptString(json_encode(['sub' => 'google-123', 'email' => 'ana@gmail.com', 'expira' => now()->subMinute()->getTimestamp()]));
        $this->completar($vencido)->assertUnprocessable();

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * La contraseña corta o sin confirmar se rechaza con errores por campo.
     */
    public function test_contrasena_invalida_responde_422(): void
    {
        $registroToken = $this->entrar('estudiante')->json('registro_token');

        $this->completar($registroToken, ['password' => 'corta', 'password_confirmation' => 'corta'])
            ->assertJsonValidationErrors('password');
        $this->completar($registroToken, ['password_confirmation' => 'otra-distinta'])
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Si el correo se registró mientras tanto, completar responde 409 sin duplicar la cuenta.
     */
    public function test_correo_registrado_mientras_tanto_responde_409(): void
    {
        $registroToken = $this->entrar('estudiante')->json('registro_token');
        User::factory()->create(['email' => 'ana@gmail.com']);

        $this->completar($registroToken)->assertStatus(409);
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * Pide entrar con Google desde la pestaña de rol indicada.
     */
    private function entrar(string $rol)
    {
        return $this->postJson('/api/auth/google', ['credential' => 'token-de-google', 'rol' => $rol]);
    }

    /**
     * Completa el registro con datos válidos, que se pueden reemplazar.
     */
    private function completar(string $registroToken, array $cambios = [])
    {
        return $this->postJson('/api/auth/google/completar', $cambios + [
            'registro_token'        => $registroToken,
            'name'                  => 'Ana P.',
            'rol'                   => 'instructor',
            'password'              => 'clave-segura',
            'password_confirmation' => 'clave-segura',
        ]);
    }
}
