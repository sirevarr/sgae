<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            // elimina la columna de la base de datos
            $table->dropColumn('seccion');
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            // revertir el cambio
            $table->string('seccion')->nullable();
        });
    }
};
