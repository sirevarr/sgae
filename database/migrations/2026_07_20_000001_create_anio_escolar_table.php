<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Anio_Escolar')) {
            return;
        }

        Schema::create('Anio_Escolar', function (Blueprint $table) {
            $table->string('codigo_ano_escolar', 20)->primary();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('estado', 20)->default('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Anio_Escolar');
    }
};
