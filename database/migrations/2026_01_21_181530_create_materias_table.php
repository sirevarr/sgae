<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_materia', 20)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->integer('creditos')->default(1);
            $table->string('estado', 20)->default('activa');
            $table->timestamps();
            
            $table->index('codigo_materia');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};