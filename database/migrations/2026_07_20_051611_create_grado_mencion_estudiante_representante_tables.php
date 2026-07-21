<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('Grado')) {
            Schema::create('Grado', function (Blueprint $table) {
                $table->string('codigo_grado', 20)->primary();
                $table->string('nombre', 80)->nullable();
                $table->string('nivel_educativo', 40)->nullable();
                $table->integer('numero_ano')->nullable();
                $table->string('estado', 20)->default('activo');
            });
        }

        if (! Schema::hasTable('Mencion')) {
            Schema::create('Mencion', function (Blueprint $table) {
                $table->id('id_mencion');
                $table->string('nombre', 100)->nullable();
                $table->string('estado', 20)->default('activo');
            });
        }

        if (! Schema::hasTable('Estudiante')) {
            Schema::create('Estudiante', function (Blueprint $table) {
                $table->string('cedula_estudiante', 20)->primary();
                $table->string('tipo_documento', 5)->nullable();
                $table->string('nacionalidad', 30)->nullable();
                $table->string('nombres', 80)->nullable();
                $table->string('apellidos', 80)->nullable();
                $table->string('genero', 5)->nullable();
                $table->date('fecha_nacimiento')->nullable();
                $table->string('lugar_nacimiento', 80)->nullable();
                $table->string('estado_nacimiento', 60)->nullable();
                $table->string('municipio_nacimiento', 60)->nullable();
                $table->string('direccion', 200)->nullable();
                $table->string('telefono', 20)->nullable();
                $table->string('correo', 120)->nullable();
                $table->text('condiciones_medicas')->nullable();
                $table->text('medicamentos')->nullable();
                $table->date('fecha_ingreso')->nullable();
                $table->string('estado_estudiante', 20)->default('activo');
                $table->date('fecha_retiro')->nullable();
                $table->string('motivo_retiro', 200)->nullable();
            });
        }

        if (! Schema::hasTable('Representante')) {
            Schema::create('Representante', function (Blueprint $table) {
                $table->integer('cedula_representante')->primary();
                $table->string('nacionalidad', 30)->nullable();
                $table->string('nombres', 80)->nullable();
                $table->string('apellidos', 80)->nullable();
                $table->string('parentesco', 30)->nullable();
                $table->string('ocupacion', 80)->nullable();
                $table->string('direccion', 200)->nullable();
                $table->string('telefono', 20)->nullable();
                $table->string('correo', 120)->nullable();
                $table->boolean('es_representante_legal')->default(false);
            });
        }

        if (! Schema::hasTable('Momento_Evaluativo')) {
            Schema::create('Momento_Evaluativo', function (Blueprint $table) {
                $table->id('id_momento');
                $table->string('codigo_ano_escolar', 20)->nullable();
                $table->integer('numero_momento')->nullable();
                $table->string('nombre', 100)->nullable();
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->decimal('porcentaje', 5, 2)->nullable();
                $table->string('estado', 20)->default('activo');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('Momento_Evaluativo');
        Schema::dropIfExists('Representante');
        Schema::dropIfExists('Estudiante');
        Schema::dropIfExists('Mencion');
        Schema::dropIfExists('Grado');
    }
};
