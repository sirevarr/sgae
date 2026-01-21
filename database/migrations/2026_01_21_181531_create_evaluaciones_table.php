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
            $table->decimal('promedio', 5, 2)->nullable()->storedAs('(nota_parcial1 + nota_parcial2 + nota_final) / 3');
            $table->date('fecha')->default(DB::raw('CURRENT_DATE'));
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->index('inscripcion_id');
            $table->index('promedio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
    }
};
