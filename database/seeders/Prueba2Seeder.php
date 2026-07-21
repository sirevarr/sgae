<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Prueba2Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Parametros
            DB::table('Parametro_Sistema')->insert([
                ['clave' => 'nota_minima_aprobatoria', 'valor' => '10'],
                ['clave' => 'nombre_sistema', 'valor' => 'SGAE'],
                ['clave' => 'version', 'valor' => '1.0.0'],
                ['clave' => 'max_estudiantes_seccion', 'valor' => '35'],
            ]);

            // Ano escolar
            DB::table('Anio_Escolar')->insert([
                ['codigo_ano_escolar' => '2025-2026', 'fecha_inicio' => '2025-09-15', 'fecha_fin' => '2026-07-15', 'estado' => 'vigente']
            ]);

            // Grados
            DB::table('Grado')->insert([
                ['codigo_grado' => '1ER', 'nombre' => 'Primer Año', 'nivel_educativo' => 'Media General', 'numero_ano' => 1],
                ['codigo_grado' => '2DO', 'nombre' => 'Segundo Año', 'nivel_educativo' => 'Media General', 'numero_ano' => 2],
            ]);

            // Menciones
            DB::table('Mencion')->insert([
                ['nombre' => 'Ciencias', 'estado' => 'activo'],
            ]);

            // Materias
            DB::table('Materia')->insert([
                ['siglas' => 'MAT', 'nombre' => 'Matemática', 'area_formacion' => 'Matemática'],
                ['siglas' => 'CAS', 'nombre' => 'Castellano', 'area_formacion' => 'Lenguaje'],
            ]);

            // Plan_Estudios (combinación mínima)
            // Resolve id_mencion dynamically
            $mencionId = DB::table('Mencion')->where('nombre', 'Ciencias')->value('id_mencion');

            DB::table('Plan_Estudios')->insert([
                ['siglas_materia' => 'MAT', 'id_mencion' => $mencionId, 'codigo_grado' => '1ER', 'codigo_ano_escolar' => '2025-2026', 'horas_semanales' => 4, 'obligatoria' => 1, 'tipo_evaluacion' => 'N', 'se_repara' => 1, 'creditos' => 3],
                ['siglas_materia' => 'CAS', 'id_mencion' => $mencionId, 'codigo_grado' => '1ER', 'codigo_ano_escolar' => '2025-2026', 'horas_semanales' => 3, 'obligatoria' => 1, 'tipo_evaluacion' => 'N', 'se_repara' => 1, 'creditos' => 2],
            ]);

            // Personal
            DB::table('Personal')->insert([
                ['cedula_personal' => 12345678, 'nombres' => 'María', 'apellidos' => 'González', 'cargo' => 'Directora', 'genero' => 'F', 'estado' => 'activo'],
                ['cedula_personal' => 87654321, 'nombres' => 'Juan', 'apellidos' => 'Pérez', 'cargo' => 'Docente', 'genero' => 'M', 'estado' => 'activo'],
            ]);

            // Docente (referencia a Personal)
            DB::table('Docente')->insert([
                ['cedula_personal' => 87654321, 'especialidad' => 'Matemáticas', 'turno' => 'M'],
            ]);

            // Institucion
            DB::table('Institucion')->insert([
                ['codigo_dea' => 'DE-0001', 'nombre' => 'U.E. Ejemplo', 'municipio' => 'Ciudad', 'estado' => 'Estado', 'zona_educativa' => 'Zona 1', 'telefono' => '0212-0000000', 'direccion' => 'Av. Principal', 'director_actual' => 12345678, 'coordinador_academico' => 87654321]
            ]);

            // Usuario (autenticacion)
            DB::table('Usuario')->insert([
                ['codigo_usuario' => 'admin', 'cedula_personal' => 12345678, 'rol' => 'administrador', 'clave_hash' => bcrypt('password'), 'estado' => 'activo', 'fecha_creacion' => now()->toDateString(), 'intentos_fallidos' => 0]
            ]);

            $usuarioId = DB::table('Usuario')->where('codigo_usuario', 'admin')->value('id_usuario');

            // Representante
            DB::table('Representante')->insert([
                ['cedula_representante' => 22222222, 'nombres' => 'Ana', 'apellidos' => 'Ramírez', 'parentesco' => 'Madre', 'telefono' => '0212-1111111']
            ]);

            // Estudiante
            DB::table('Estudiante')->insert([
                ['cedula_estudiante' => 'V-10000000', 'tipo_documento' => 'V', 'nombres' => 'Pedro', 'apellidos' => 'Gómez', 'genero' => 'M', 'fecha_nacimiento' => '2008-05-10', 'fecha_ingreso' => '2025-09-15', 'estado_estudiante' => 'activo']
            ]);

            // Ficha_Antropometrica
            DB::table('Ficha_Antropometrica')->insert([
                ['cedula_estudiante' => 'V-10000000', 'codigo_ano_escolar' => '2025-2026', 'estatura' => 1.6, 'peso' => 55.0, 'fecha_medicion' => '2025-09-20']
            ]);

            // Seccion
            DB::table('Seccion')->insert([
                ['codigo_seccion' => '1ER-A', 'letra' => 'A', 'codigo_grado' => '1ER', 'codigo_ano_escolar' => '2025-2026', 'id_mencion' => $mencionId, 'cedula_docente_guia' => 87654321, 'capacidad_maxima' => 30, 'turno' => 'M']
            ]);

            // Matricula
            DB::table('Matricula')->insert([
                ['cedula_estudiante' => 'V-10000000', 'codigo_ano_escolar' => '2025-2026', 'codigo_seccion' => '1ER-A', 'cedula_representante' => 22222222, 'fecha_matricula' => '2025-09-15', 'numero_lista' => 1, 'estado_matricula' => 'activa']
            ]);

            // Asignacion_Docente
            DB::table('Asignacion_Docente')->insert([
                ['cedula_docente' => 87654321, 'codigo_seccion' => '1ER-A', 'siglas_materia' => 'MAT', 'id_mencion' => $mencionId, 'codigo_grado' => '1ER', 'codigo_ano_escolar' => '2025-2026', 'horas_asignadas' => 4]
            ]);

            // Momento_Evaluativo
            DB::table('Momento_Evaluativo')->insert([
                ['numero_momento' => 1, 'codigo_ano_escolar' => '2025-2026', 'nombre' => '1er Parcial', 'fecha_inicio' => '2025-10-01', 'fecha_fin' => '2025-10-05', 'porcentaje' => 40, 'estado' => 'planificado']
            ]);

            // Evaluacion
            DB::table('Evaluacion')->insert([
                ['cedula_estudiante' => 'V-10000000', 'siglas_materia' => 'MAT', 'id_mencion' => $mencionId, 'codigo_grado' => '1ER', 'codigo_ano_escolar' => '2025-2026', 'numero_momento' => 1, 'nota' => 12.5, 'fecha_evaluacion' => '2025-10-02', 'cedula_docente_evaluador' => 87654321]
            ]);

            // Materia_Pendiente (none by default) - insert a sample
            DB::table('Materia_Pendiente')->insert([
                ['cedula_estudiante' => 'V-10000000', 'siglas_materia' => 'CAS', 'id_mencion' => $mencionId, 'codigo_grado' => '1ER', 'codigo_ano_escolar_origen' => '2025-2026', 'estado' => 'pendiente']
            ]);

            // Documento_Emitido (placeholder binary)
            DB::table('Documento_Emitido')->insert([
                ['tipo_documento' => 'constancia', 'cedula_estudiante' => 'V-10000000', 'codigo_ano_escolar' => '2025-2026', 'folio' => 'F-0001', 'id_usuario_emisor' => $usuarioId, 'contenido_pdf' => DB::raw('0x0')]
            ]);

            // Login and Auditoria sample entries
            DB::table('Login')->insert([
                ['id_usuario' => $usuarioId, 'fecha' => now()->toDateString(), 'hora' => now()->toTimeString(), 'ip_acceso' => '127.0.0.1', 'tipo_acceso' => 'E', 'exitoso' => 1]
            ]);

            DB::table('Auditoria')->insert([
                ['id_usuario' => $usuarioId, 'tabla_afectada' => 'Usuario', 'id_registro_afectado' => (string) $usuarioId, 'operacion' => 'I', 'valores_nuevos' => 'Usuario admin creado']
            ]);
        });
    }
}
