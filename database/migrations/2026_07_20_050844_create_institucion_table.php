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
        Schema::create('Institucion', function (Blueprint $table) {
            $table->string('codigo_dea')->primary();
            $table->string('nombre')->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('municipio')->nullable();
            $table->string('estado')->nullable();
            $table->string('zona_educativa')->nullable();
            $table->unsignedInteger('director_actual')->nullable();
            $table->unsignedInteger('coordinador_academico')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Institucion');
    }
};
