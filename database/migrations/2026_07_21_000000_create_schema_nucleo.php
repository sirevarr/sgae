<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('sql/schema_nucleo.sql');

        if (! file_exists($path)) {
            throw new \RuntimeException("SQL schema file not found: {$path}");
        }

        $sql = file_get_contents($path);

        // Split so CREATE VIEW statements are sent in their own batch.
        $parts = preg_split('/\r?\n(?=CREATE VIEW )/i', $sql);

        if ($parts === false || count($parts) === 1) {
            DB::unprepared($sql);
            return;
        }

        // Execute the initial DDL (tables, drops, etc.)
        DB::unprepared($parts[0]);

        // Execute each CREATE VIEW as a separate batch
        for ($i = 1; $i < count($parts); $i++) {
            $viewSql = ltrim($parts[$i]);
            if ($viewSql !== '') {
                DB::unprepared($viewSql);
            }
        }
    }

    public function down(): void
    {
        $sql = <<<'SQL'
SET NOCOUNT ON;

IF OBJECT_ID('dbo.vw_Evaluacion_Resultado', 'V') IS NOT NULL
    DROP VIEW dbo.vw_Evaluacion_Resultado;
IF OBJECT_ID('dbo.vw_Seccion_Conteo', 'V') IS NOT NULL
    DROP VIEW dbo.vw_Seccion_Conteo;

IF OBJECT_ID('dbo.Documento_Emitido', 'U') IS NOT NULL DROP TABLE dbo.Documento_Emitido;
IF OBJECT_ID('dbo.Materia_Pendiente', 'U') IS NOT NULL DROP TABLE dbo.Materia_Pendiente;
IF OBJECT_ID('dbo.Evaluacion', 'U') IS NOT NULL DROP TABLE dbo.Evaluacion;
IF OBJECT_ID('dbo.Matricula', 'U') IS NOT NULL DROP TABLE dbo.Matricula;
IF OBJECT_ID('dbo.Asignacion_Docente', 'U') IS NOT NULL DROP TABLE dbo.Asignacion_Docente;
IF OBJECT_ID('dbo.Seccion', 'U') IS NOT NULL DROP TABLE dbo.Seccion;
IF OBJECT_ID('dbo.Plan_Estudios', 'U') IS NOT NULL DROP TABLE dbo.Plan_Estudios;
IF OBJECT_ID('dbo.Ficha_Antropometrica', 'U') IS NOT NULL DROP TABLE dbo.Ficha_Antropometrica;
IF OBJECT_ID('dbo.Representante', 'U') IS NOT NULL DROP TABLE dbo.Representante;
IF OBJECT_ID('dbo.Auditoria', 'U') IS NOT NULL DROP TABLE dbo.Auditoria;
IF OBJECT_ID('dbo.Login', 'U') IS NOT NULL DROP TABLE dbo.Login;
IF OBJECT_ID('dbo.Usuario', 'U') IS NOT NULL DROP TABLE dbo.Usuario;
IF OBJECT_ID('dbo.Institucion', 'U') IS NOT NULL DROP TABLE dbo.Institucion;
IF OBJECT_ID('dbo.Docente', 'U') IS NOT NULL DROP TABLE dbo.Docente;
IF OBJECT_ID('dbo.Personal', 'U') IS NOT NULL DROP TABLE dbo.Personal;
IF OBJECT_ID('dbo.Estudiante', 'U') IS NOT NULL DROP TABLE dbo.Estudiante;
IF OBJECT_ID('dbo.Materia', 'U') IS NOT NULL DROP TABLE dbo.Materia;
IF OBJECT_ID('dbo.Mencion', 'U') IS NOT NULL DROP TABLE dbo.Mencion;
IF OBJECT_ID('dbo.Grado', 'U') IS NOT NULL DROP TABLE dbo.Grado;
IF OBJECT_ID('dbo.Anio_Escolar', 'U') IS NOT NULL DROP TABLE dbo.Anio_Escolar;
IF OBJECT_ID('dbo.Parametro_Sistema', 'U') IS NOT NULL DROP TABLE dbo.Parametro_Sistema;
SQL;

        DB::unprepared($sql);
    }
};
