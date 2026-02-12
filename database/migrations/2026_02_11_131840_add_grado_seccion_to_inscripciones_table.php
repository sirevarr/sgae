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
        Schema::table('inscripciones', function (Blueprint $table) {
            // Agregar grado (capturado en el momento de la inscripción)
            $table->string('grado')->nullable()->after('periodo')->comment('Grado del estudiante al momento de la inscripción');

            // Agregar sección (capturada en el momento de la inscripción)
            $table->string('seccion')->nullable()->after('grado')->comment('Sección del estudiante al momento de la inscripción');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            // revierte la migración para deshacer cambios
            $table->dropColumn(['grado', 'seccion']);
        });
    }
};