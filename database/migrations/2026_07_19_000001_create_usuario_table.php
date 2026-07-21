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
        if (Schema::hasTable('Usuario')) {
            return;
        }

        Schema::create('Usuario', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('codigo_usuario', 30)->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->integer('cedula_personal')->nullable();
            $table->string('rol', 50)->default('usuario');
            $table->string('clave_hash');
            $table->string('estado', 20)->default('activo');
            $table->date('fecha_creacion')->nullable();
            $table->date('ultimo_acceso')->nullable();
            $table->integer('intentos_fallidos')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Usuario');
    }
};
