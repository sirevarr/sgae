<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Usuario;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = Usuario::factory()->create();

        $response = $this->post('/login', [
            'codigo_usuario' => $user->codigo_usuario,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_admin_user_can_authenticate_with_default_credentials(): void
    {
        Usuario::factory()->create([
            'codigo_usuario' => 'admin',
            'email' => 'admin@sgae.test',
            'clave_hash' => bcrypt('password'),
            'estado' => 'activo',
        ]);

        $response = $this->post('/login', [
            'codigo_usuario' => 'admin',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_admin_fallback_credentials_can_authenticate_without_a_seeded_row(): void
    {
        $response = $this->post('/login', [
            'codigo_usuario' => 'admin',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
