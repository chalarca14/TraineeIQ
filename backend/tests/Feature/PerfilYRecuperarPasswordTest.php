<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Cambio de nombre en el perfil y "¿Olvidaste tu contraseña?".
 */
class PerfilYRecuperarPasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cualquier rol cambia su nombre y la respuesta trae el usuario actualizado.
     */
    public function test_cambia_el_nombre(): void
    {
        $usuario = User::factory()->create(['rol' => 'instructor', 'name' => 'Nombre Viejo']);
        Sanctum::actingAs($usuario);

        $this->patchJson('/api/usuario/perfil', ['name' => 'Nombre Nuevo'])
            ->assertOk()
            ->assertJsonPath('usuario.name', 'Nombre Nuevo');

        $this->assertSame('Nombre Nuevo', $usuario->fresh()->name);
    }

    /**
     * El correo no se puede cambiar por este endpoint aunque se mande.
     */
    public function test_el_perfil_no_cambia_el_correo(): void
    {
        $usuario = User::factory()->create(['rol' => 'estudiante', 'email' => 'antes@correo.com']);
        Sanctum::actingAs($usuario);

        $this->patchJson('/api/usuario/perfil', ['name' => 'Ana', 'email' => 'otro@correo.com'])->assertOk();

        $this->assertSame('antes@correo.com', $usuario->fresh()->email);
    }

    /**
     * Nombre vacío o muy corto: 422 con el mensaje en español.
     */
    public function test_el_nombre_es_obligatorio(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));

        $this->patchJson('/api/usuario/perfil', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'Escribe tu nombre.']);

        $this->patchJson('/api/usuario/perfil', ['name' => 'A'])->assertUnprocessable();
    }

    /**
     * Sin sesión no se puede cambiar el nombre.
     */
    public function test_el_perfil_requiere_sesion(): void
    {
        $this->patchJson('/api/usuario/perfil', ['name' => 'Ana'])->assertUnauthorized();
    }

    /**
     * Con un correo registrado se envía el correo con el enlace.
     */
    public function test_envia_el_enlace_a_un_correo_registrado(): void
    {
        Notification::fake();
        $usuario = User::factory()->create(['email' => 'ana@correo.com']);

        $this->postJson('/api/password/olvide', ['email' => 'ana@correo.com'])->assertOk();

        Notification::assertSentTo($usuario, ResetPassword::class);
    }

    /**
     * Con un correo que no existe responde lo mismo y no envía nada (no revela qué correos hay).
     */
    public function test_un_correo_desconocido_recibe_la_misma_respuesta(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'ana@correo.com']);

        $existe = $this->postJson('/api/password/olvide', ['email' => 'ana@correo.com'])->json('message');
        $noExiste = $this->postJson('/api/password/olvide', ['email' => 'nadie@correo.com'])
            ->assertOk()
            ->json('message');

        $this->assertSame($existe, $noExiste);
        Notification::assertCount(1);
    }

    /**
     * El enlace del correo apunta al frontend (/restablecer) con el token y el correo.
     */
    public function test_el_correo_lleva_al_frontend(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'https://trainee.test']);
        $usuario = User::factory()->create(['email' => 'ana@correo.com']);

        $this->postJson('/api/password/olvide', ['email' => 'ana@correo.com']);

        Notification::assertSentTo($usuario, ResetPassword::class, function ($aviso) use ($usuario) {
            $enlace = $aviso->toMail($usuario)->actionUrl;

            return str_starts_with($enlace, 'https://trainee.test/restablecer?')
                && str_contains($enlace, 'token=' . $aviso->token)
                && str_contains($enlace, 'email=ana%40correo.com');
        });
    }

    /**
     * Con un token válido se guarda la contraseña nueva y se cierran las sesiones abiertas.
     */
    public function test_restablece_la_contrasena_y_cierra_sesiones(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@correo.com', 'password' => Hash::make('vieja12345')]);
        $usuario->createToken('sesion');
        $token = Password::createToken($usuario);

        $this->postJson('/api/password/restablecer', [
            'token'                 => $token,
            'email'                 => 'ana@correo.com',
            'password'              => 'nueva12345',
            'password_confirmation' => 'nueva12345',
        ])->assertOk();

        $this->assertTrue(Hash::check('nueva12345', $usuario->fresh()->password));
        $this->assertSame(0, $usuario->tokens()->count());
    }

    /**
     * Token inventado o de otro correo: 422 con un solo mensaje y la contraseña no cambia.
     */
    public function test_un_token_invalido_no_cambia_nada(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@correo.com', 'password' => Hash::make('vieja12345')]);

        $this->postJson('/api/password/restablecer', [
            'token'                 => 'inventado',
            'email'                 => 'ana@correo.com',
            'password'              => 'nueva12345',
            'password_confirmation' => 'nueva12345',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'El enlace no es válido o ya venció. Pide uno nuevo.');

        $this->assertTrue(Hash::check('vieja12345', $usuario->fresh()->password));
    }

    /**
     * El token sirve una sola vez.
     */
    public function test_el_enlace_no_se_puede_usar_dos_veces(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@correo.com']);
        $datos = [
            'token'                 => Password::createToken($usuario),
            'email'                 => 'ana@correo.com',
            'password'              => 'nueva12345',
            'password_confirmation' => 'nueva12345',
        ];

        $this->postJson('/api/password/restablecer', $datos)->assertOk();
        $this->postJson('/api/password/restablecer', $datos)->assertUnprocessable();
    }

    /**
     * La contraseña nueva cumple las mismas reglas: mínimo 8 y confirmada.
     */
    public function test_la_contrasena_nueva_se_valida(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@correo.com']);
        $token = Password::createToken($usuario);

        $this->postJson('/api/password/restablecer', [
            'token' => $token, 'email' => 'ana@correo.com',
            'password' => 'corta', 'password_confirmation' => 'corta',
        ])->assertJsonValidationErrors(['password' => 'La nueva contraseña debe tener al menos 8 caracteres.']);

        $this->postJson('/api/password/restablecer', [
            'token' => $token, 'email' => 'ana@correo.com',
            'password' => 'nueva12345', 'password_confirmation' => 'otra12345',
        ])->assertJsonValidationErrors(['password' => 'Las contraseñas no coinciden.']);
    }
}
