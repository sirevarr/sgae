<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Materia', function (Blueprint $table) {
            $table->string('siglas', 20)->primary();
            $table->string('nombre', 100)->nullable();
            $table->string('area_formacion', 100)->nullable();
        });

        Schema::create('Seccion', function (Blueprint $table) {
            $table->string('codigo_seccion', 20)->primary();
            $table->string('codigo_ano_escolar', 20)->nullable();
            $table->string('codigo_grado', 20)->nullable();
            $table->string('codigo_mencion', 20)->nullable();
            $table->string('codigo_docente_guia', 20)->nullable();
            $table->string('estado', 20)->default('activa');
            $table->integer('cupos')->nullable();
        });

        Schema::create('MomentoEvaluativo', function (Blueprint $table) {
            $table->id('id_momento');
            $table->string('codigo_ano_escolar', 20)->nullable();
            $table->integer('numero_momento')->nullable();
            $table->string('nombre', 100)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->decimal('porcentaje', 5, 2)->nullable();
            $table->string('estado', 20)->default('activo');
        });

        Schema::create('Evaluacion', function (Blueprint $table) {
            $table->id('id_evaluacion');
            $table->string('codigo_ano_escolar', 20)->nullable();
            $table->string('codigo_seccion', 20)->nullable();
            $table->string('siglas_materia', 20)->nullable();
            $table->string('cedula_estudiante', 20)->nullable();
            $table->string('cedula_docente_evaluador', 20)->nullable();
            $table->integer('momento')->nullable();
            $table->decimal('nota', 4, 2)->nullable();
            $table->date('fecha_evaluacion')->nullable();
            $table->string('estado', 20)->default('registrada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Evaluacion');
        Schema::dropIfExists('MomentoEvaluativo');
        Schema::dropIfExists('Seccion');
        Schema::dropIfExists('Materia');
    }
};
