<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Delega toda la carga de datos al DemoDataSeeder.
     */
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);
    }
}
