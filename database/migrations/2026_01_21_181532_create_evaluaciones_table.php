<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones', function (Blueprint $table) {
        $table->id();
        $table->foreignId('inscripcion_id')->unique()->constrained('inscripciones')->onDelete('cascade');
        $table->decimal('nota_parcial1', 5, 2)->nullable();
        $table->decimal('nota_parcial2', 5, 2)->nullable();
        $table->decimal('nota_final', 5, 2)->nullable();
        
        // Cambia el 'storedAs' por un decimal simple
        $table->decimal('promedio', 5, 2)->nullable();
        $table->string('estado', 20)->nullable(); // <--- Esta es la que falta 
        
        $table->date('fecha')->useCurrent();
        $table->text('observaciones')->nullable();
        $table->timestamps();
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
    }
};
