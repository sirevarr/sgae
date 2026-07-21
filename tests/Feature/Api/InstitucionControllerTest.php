<?php

namespace Tests\Feature\Api;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitucionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_creates_institution_when_missing(): void
    {
        $user = Usuario::factory()->create([
            'codigo_usuario' => 'inst-test-' . uniqid(),
            'email' => 'inst-test-' . uniqid() . '@sgae.test',
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($user, 'web')->putJson('/api/institucion/TEST-001', [
            'nombre' => 'Colegio de Prueba',
            'direccion' => 'Calle 123',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('Institucion', [
            'codigo_dea' => 'TEST-001',
            'nombre' => 'Colegio de Prueba',
        ]);
    }
}
