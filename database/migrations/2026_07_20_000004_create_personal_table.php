<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('Personal', function (Blueprint $table) {
            $table->integer('cedula_personal')->primary();
            $table->string('nombres', 100)->nullable();
            $table->string('apellidos', 100)->nullable();
            $table->string('cargo', 100)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 100)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('genero', 20)->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->text('observaciones')->nullable();
        });

        Schema::create('Docente', function (Blueprint $table) {
            $table->integer('cedula_personal')->primary();
            $table->string('especialidad', 100)->nullable();
            $table->string('turno', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Docente');
        Schema::dropIfExists('Personal');
    }
};
