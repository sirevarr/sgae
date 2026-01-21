<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->onDelete('cascade');
            $table->foreignId('materia_id')->constrained('materias')->onDelete('cascade');
            $table->string('periodo', 20); // Ej: 2025-1
            $table->char('seccion', 1)->default('A');
            $table->date('fecha_inscripcion')->default(DB::raw('CURRENT_DATE'));
            $table->enum('estado', ['inscrito', 'cursando', 'aprobado', 'reprobado', 'retirado'])->default('inscrito');
            $table->timestamps();
            
            // Prevenir inscripciones duplicadas
            $table->unique(['estudiante_id', 'materia_id', 'periodo']);
            
            $table->index('periodo');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
