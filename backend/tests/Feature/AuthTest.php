<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_and_receive_a_sanctum_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Aprendiz de prueba',
            'email' => 'aprendiz@example.com',
            'password' => 'password-seguro',
            'rol' => 'estudiante',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.rol', 'estudiante')
            ->assertJsonPath('user.modo_preferido', null)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
            ->assertJsonMissingPath('user.password');

        $user = User::where('email', 'aprendiz@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('password-seguro', $user->password));
        $this->assertDatabaseMissing('users', ['password' => 'password-seguro']);
    }

    public function test_registration_rejects_administrator_role(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Usuario no permitido',
            'email' => 'admin@example.com',
            'password' => 'password-seguro',
            'rol' => 'administrador',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('rol');

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
    }

    public function test_instructor_can_register(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Instructor de prueba',
            'email' => 'instructor@example.com',
            'password' => 'password-seguro',
            'rol' => 'instructor',
        ])->assertCreated()
            ->assertJsonPath('user.rol', 'instructor');
    }

    public function test_user_can_log_in_and_log_out_with_a_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email' => 'aprendiz@example.com',
            'password' => Hash::make('password-seguro'),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'aprendiz@example.com',
            'password' => 'password-seguro',
            'rol' => 'estudiante',
        ])->assertOk();

        $token = $loginResponse->json('token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'auth_token',
        ]);

        app('auth')->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'aprendiz@example.com',
            'password' => Hash::make('password-seguro'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'aprendiz@example.com',
            'password' => 'incorrecta',
            'rol' => 'estudiante',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Credenciales incorrectas');
    }

    public function test_login_rejects_credentials_for_a_different_role(): void
    {
        User::factory()->create([
            'email' => 'aprendiz@example.com',
            'password' => Hash::make('password-seguro'),
            'rol' => 'estudiante',
        ]);

        $this->postJson('/api/login', [
            'email' => 'aprendiz@example.com',
            'password' => 'password-seguro',
            'rol' => 'instructor',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Credenciales incorrectas');
    }

    public function test_instructor_can_log_in_with_the_instructor_role(): void
    {
        User::factory()->create([
            'email' => 'instructor@example.com',
            'password' => Hash::make('password-seguro'),
            'rol' => 'instructor',
        ]);

        $this->postJson('/api/login', [
            'email' => 'instructor@example.com',
            'password' => 'password-seguro',
            'rol' => 'instructor',
        ])->assertOk()
            ->assertJsonPath('user.rol', 'instructor');
    }

    public function test_student_can_read_and_update_preferred_mode(): void
    {
        $user = User::factory()->create(['modo_preferido' => null]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/usuario/modo')
            ->assertOk()
            ->assertJsonPath('modo_preferido', null);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/usuario/modo', ['modo_preferido' => 'personal'])
            ->assertOk()
            ->assertJsonPath('modo_preferido', 'personal');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'modo_preferido' => 'personal',
        ]);
    }

    public function test_preferred_mode_endpoint_rejects_invalid_values(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/usuario/modo', ['modo_preferido' => 'administrador'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('modo_preferido');
    }

    public function test_only_students_can_use_preferred_mode_endpoint(): void
    {
        $instructor = User::factory()->create(['rol' => 'instructor']);

        $this->actingAs($instructor, 'sanctum')
            ->getJson('/api/usuario/modo')
            ->assertForbidden();

        $this->actingAs($instructor, 'sanctum')
            ->patchJson('/api/usuario/modo', ['modo_preferido' => 'personal'])
            ->assertForbidden();
    }

    public function test_preferred_mode_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/usuario/modo')->assertUnauthorized();
        $this->patchJson('/api/usuario/modo', ['modo_preferido' => 'grupo'])->assertUnauthorized();
    }
}
