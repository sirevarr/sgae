<?php

namespace Tests\Feature\Api;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stats_api_can_be_accessed_by_authenticated_web_user(): void
    {
        $user = Usuario::factory()->create([
            'codigo_usuario' => 'api-user-' . uniqid(),
            'email' => 'api-user-' . uniqid() . '@sgae.test',
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($user, 'web')->getJson('/api/dashboard-stats');

        $response->assertOk();
        $response->assertJsonStructure([
            'estudiantesCount',
            'docentesCount',
            'seccionesCount',
            'materiasCount',
            'promedioGlobal',
            'porcentajeAprobados',
            'anioVigente',
            'momentoActual',
        ]);
    }
}
