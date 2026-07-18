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
            // Relación con Estudiantes
            $table->foreignId('estudiante_id')->constrained('estudiantes')->onDelete('cascade');
            // Relación con Materias
            $table->foreignId('materia_id')->constrained('materias')->onDelete('cascade');
            
            $table->string('periodo', 20); 
            $table->string('seccion', 50); 
            $table->date('fecha_inscripcion');
            $table->string('estado', 20)->default('activa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
