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
        Schema::create('Matricula', function (Blueprint $table) {
            $table->id('id_matricula');
            $table->string('cedula_estudiante', 20)->nullable();
            $table->string('codigo_ano_escolar', 20)->nullable();
            $table->string('codigo_seccion', 20)->nullable();
            $table->string('cedula_representante', 20)->nullable();
            $table->date('fecha_matricula')->nullable();
            $table->integer('numero_lista')->nullable();
            $table->string('condicion_ingreso', 50)->nullable();
            $table->string('procedencia', 100)->nullable();
            $table->integer('ano_inicio_cursante')->nullable();
            $table->string('estado_matricula', 20)->default('activa');
            $table->text('observaciones')->nullable();
            $table->date('fecha_retiro')->nullable();
            $table->string('motivo_retiro', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Matricula');
    }
};
