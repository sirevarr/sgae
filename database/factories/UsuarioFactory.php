<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Usuario>
 */
class UsuarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_usuario' => 'user' . $this->faker->unique()->numberBetween(1, 999999),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'clave_hash' => Hash::make('password'),
            'estado' => 'activo',
            'fecha_creacion' => now()->toDateString(),
            'intentos_fallidos' => 0,
            'remember_token' => Str::random(10),
        ];
    }
}
