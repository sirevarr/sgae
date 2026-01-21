<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('cedula', 20)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->char('genero', 1);
            $table->date('fecha_nacimiento');
            $table->string('lugar_nacimiento', 150)->nullable();
            $table->text('direccion')->nullable();
            $table->string('email', 150)->unique()->nullable();
            $table->string('telefono', 20)->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'graduado', 'retirado'])->default('activo');
            $table->timestamps();
            
            // Índices para búsquedas
            $table->index(['nombres', 'apellidos']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
