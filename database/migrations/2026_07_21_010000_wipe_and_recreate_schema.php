<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop all user objects except the migrations table to allow Laravel to record this migration.
        $dropSql = <<<'SQL'
SET NOCOUNT ON;

-- Drop foreign key constraints
DECLARE @sql NVARCHAR(MAX) = N'';
SELECT @sql += N'ALTER TABLE ' + QUOTENAME(SCHEMA_NAME(tp.schema_id)) + N'.' + QUOTENAME(tp.name) + N' DROP CONSTRAINT ' + QUOTENAME(fk.name) + N';' + CHAR(13)
FROM sys.foreign_keys fk
JOIN sys.tables tp ON fk.parent_object_id = tp.object_id
WHERE tp.name <> 'migrations';
IF @sql <> '' EXEC sp_executesql @sql;

-- Drop views
SET @sql = N'';
SELECT @sql += N'DROP VIEW ' + QUOTENAME(SCHEMA_NAME(o.schema_id)) + N'.' + QUOTENAME(o.name) + N';' + CHAR(13)
FROM sys.objects o
WHERE o.type = 'V';
IF @sql <> '' EXEC sp_executesql @sql;

-- Drop procedures
SET @sql = N'';
SELECT @sql += N'DROP PROCEDURE ' + QUOTENAME(SCHEMA_NAME(o.schema_id)) + N'.' + QUOTENAME(o.name) + N';' + CHAR(13)
FROM sys.objects o
WHERE o.type IN ('P');
IF @sql <> '' EXEC sp_executesql @sql;

-- Drop functions (scalar and table-valued)
SET @sql = N'';
SELECT @sql += N'DROP FUNCTION ' + QUOTENAME(SCHEMA_NAME(o.schema_id)) + N'.' + QUOTENAME(o.name) + N';' + CHAR(13)
FROM sys.objects o
WHERE o.type IN ('FN','TF','IF');
IF @sql <> '' EXEC sp_executesql @sql;

-- Drop user tables (exclude migrations)
SET @sql = N'';
SELECT @sql += N'DROP TABLE ' + QUOTENAME(SCHEMA_NAME(t.schema_id)) + N'.' + QUOTENAME(t.name) + N';' + CHAR(13)
FROM sys.tables t
WHERE t.is_ms_shipped = 0 AND t.name <> 'migrations';
IF @sql <> '' EXEC sp_executesql @sql;

-- Drop types
SET @sql = N'';
SELECT @sql += N'DROP TYPE ' + QUOTENAME(SCHEMA_NAME(t.schema_id)) + N'.' + QUOTENAME(t.name) + N';' + CHAR(13)
FROM sys.types t
WHERE t.is_user_defined = 1;
IF @sql <> '' EXEC sp_executesql @sql;

SQL;

        DB::unprepared($dropSql);

        // Recreate schema from file (same splitting logic used previously)
        $path = database_path('sql/schema_nucleo.sql');
        if (! file_exists($path)) {
            throw new \RuntimeException("SQL schema file not found: {$path}");
        }

        $sql = file_get_contents($path);
        $parts = preg_split('/\r?\n(?=CREATE VIEW )/i', $sql);
        if ($parts === false || count($parts) === 1) {
            DB::unprepared($sql);
            return;
        }

        DB::unprepared($parts[0]);
        for ($i = 1; $i < count($parts); $i++) {
            $viewSql = ltrim($parts[$i]);
            if ($viewSql !== '') {
                DB::unprepared($viewSql);
            }
        }
    }

    public function down(): void
    {
        // No-op
    }
};
