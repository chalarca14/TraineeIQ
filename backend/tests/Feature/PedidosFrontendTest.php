<?php

namespace Tests\Feature;

use App\Models\Grupo;
use App\Models\Guia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Cambios pedidos por el frontend: límite al unirse a grupos, cambio de contraseña,
 * conteos de los grupos y contexto de TraineeIQ en el chat.
 */
class PedidosFrontendTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Probar códigos de acceso tiene un límite de 10 intentos por minuto.
     */
    public function test_unirse_a_un_grupo_tiene_limite_de_intentos(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));

        foreach (range(1, 10) as $intento) {
            $this->postJson('/api/grupos/unirse', ['codigo_acceso' => "NO{$intento}"])->assertUnprocessable();
        }

        $this->postJson('/api/grupos/unirse', ['codigo_acceso' => 'NO11'])->assertStatus(429);
    }

    /**
     * Con la contraseña actual correcta, la nueva queda guardada.
     */
    public function test_cambia_la_contrasena(): void
    {
        $usuario = $this->usuarioConClave('clave-actual-1');

        $this->patchJson('/api/usuario/password', [
            'password_actual'       => 'clave-actual-1',
            'password'              => 'clave-nueva-123',
            'password_confirmation' => 'clave-nueva-123',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('clave-nueva-123', $usuario->fresh()->password));
    }

    /**
     * Una contraseña actual incorrecta responde 422 y no cambia nada.
     */
    public function test_rechaza_una_contrasena_actual_incorrecta(): void
    {
        $usuario = $this->usuarioConClave('clave-actual-1');

        $this->patchJson('/api/usuario/password', [
            'password_actual'       => 'otra-clave',
            'password'              => 'clave-nueva-123',
            'password_confirmation' => 'clave-nueva-123',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.password_actual.0', 'La contraseña actual no es correcta.');

        $this->assertTrue(Hash::check('clave-actual-1', $usuario->fresh()->password));
    }

    /**
     * La nueva contraseña exige confirmación y al menos 8 caracteres.
     */
    public function test_valida_la_nueva_contrasena(): void
    {
        $this->usuarioConClave('clave-actual-1');

        $this->patchJson('/api/usuario/password', [
            'password_actual'       => 'clave-actual-1',
            'password'              => 'corta',
            'password_confirmation' => 'distinta',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    /**
     * El instructor también puede cambiarla, y sin sesión responde 401.
     */
    public function test_sirve_para_instructores_y_exige_sesion(): void
    {
        $this->patchJson('/api/usuario/password', [])->assertUnauthorized();

        $this->usuarioConClave('clave-actual-1', 'instructor');

        $this->patchJson('/api/usuario/password', [
            'password_actual'       => 'clave-actual-1',
            'password'              => 'clave-nueva-123',
            'password_confirmation' => 'clave-nueva-123',
        ])->assertOk();
    }

    /**
     * El instructor ve todas sus guías y mini proyectos; el estudiante, solo los liberados.
     */
    public function test_los_grupos_traen_conteos_de_guias_y_mini_proyectos(): void
    {
        $instructor = User::factory()->create(['rol' => 'instructor']);
        $estudiante = User::factory()->create(['rol' => 'estudiante']);

        $grupo = Grupo::create([
            'nombre'        => 'ADSO',
            'instructor_id' => $instructor->id,
            'codigo_acceso' => 'ABC123',
            'semana_actual' => 1,
        ]);
        $grupo->estudiantes()->attach($estudiante->id);

        $guia = Guia::create(['nombre' => 'Guía', 'origen' => 'grupo', 'grupo_id' => $grupo->id]);
        $this->crearSemanaConProyectos($guia, 1, 3);
        $this->crearSemanaConProyectos($guia, 2, 3);

        // Una guía personal del estudiante no cuenta en el grupo
        $personal = Guia::create(['nombre' => 'Mía', 'origen' => 'personal', 'usuario_id' => $estudiante->id]);
        $this->crearSemanaConProyectos($personal, 1, 3);

        Sanctum::actingAs($instructor);
        $this->getJson('/api/grupos')
            ->assertOk()
            ->assertJsonPath('data.0.guias_count', 1)
            ->assertJsonPath('data.0.mini_proyectos_count', 6)
            ->assertJsonPath('data.0.estudiantes_count', 1);

        Sanctum::actingAs($estudiante);
        $this->getJson('/api/grupos')
            ->assertOk()
            ->assertJsonPath('data.0.guias_count', 1)
            ->assertJsonPath('data.0.mini_proyectos_count', 3)
            ->assertJsonPath('data.0.instructor.name', $instructor->name);
    }

    /**
     * El chat libre le explica a Gemini qué es TraineeIQ.
     */
    public function test_el_chat_libre_incluye_el_contexto_de_traineeiq(): void
    {
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Hola']]]]]])]);
        Sanctum::actingAs(User::factory()->create(['rol' => 'estudiante']));

        $conversacion = $this->postJson('/api/conversaciones', ['tipo' => 'libre'])->assertCreated()->json('id');
        $this->postJson("/api/conversaciones/{$conversacion}/mensajes", ['contenido' => '¿Qué es TraineeIQ?'])->assertOk();

        $prompt = Http::recorded()->first()[0]['contents'][0]['parts'][0]['text'];

        $this->assertStringContainsString('TraineeIQ es una plataforma web del SENA', $prompt);
    }

    /**
     * Crea un usuario con esta contraseña y lo deja con sesión iniciada.
     */
    private function usuarioConClave(string $clave, string $rol = 'estudiante'): User
    {
        $usuario = User::factory()->create(['rol' => $rol, 'password' => Hash::make($clave)]);
        Sanctum::actingAs($usuario);

        return $usuario;
    }

    /**
     * Crea una semana lista con la cantidad indicada de mini proyectos.
     */
    private function crearSemanaConProyectos(Guia $guia, int $numero, int $cantidad): void
    {
        $semana = $guia->semanas()->create([
            'numero_semana'     => $numero,
            'titulo'            => "Semana {$numero}",
            'resumen'           => 'Resumen.',
            'contenido_texto'   => "Texto {$numero}",
            'hash_contenido'    => hash('sha256', "Texto {$numero}"),
            'estado_generacion' => 'listo',
        ]);

        foreach (range(1, $cantidad) as $orden) {
            $semana->miniProyectos()->create([
                'guia_id'                => $guia->id,
                'orden'                  => $orden,
                'titulo'                 => "Proyecto {$orden}",
                'descripcion'            => 'Descripción',
                'requisitos_funcionales' => ['Requisito'],
                'buenas_practicas'       => ['Práctica'],
                'criterios_evaluacion'   => [['criterio' => 'Funciona', 'peso' => 100]],
            ]);
        }
    }
}
