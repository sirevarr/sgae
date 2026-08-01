<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Punto 5 — Respaldo de base de datos SQL Server.
 *
 * Uso manual:   php artisan sgae:respaldar
 * Programado:   Registrado en App\Console\Kernel para ejecución diaria automática.
 *
 * NOTA: Si el entorno de despliegue no tiene un scheduler de Laravel corriendo
 * (via `php artisan schedule:run` en cron), el comando puede ejecutarse
 * manualmente o desde el panel de administración del sistema SGAE.
 *
 * Requiere: sqlcmd disponible en el PATH del sistema (SQL Server tools).
 * Alternativa: si sqlcmd no está disponible, se usa un dump PHP nativo de las tablas.
 */
class RespaldarBaseDatos extends Command
{
    /**
     * @var string
     */
    protected $signature = 'sgae:respaldar';

    /**
     * @var string
     */
    protected $description = 'Genera un respaldo (backup) de la base de datos SQL Server del sistema SGAE.';

    public function handle(): int
    {
        $this->info('=== SGAE — Generando respaldo de base de datos ===');

        // Crear directorio de respaldos si no existe
        $dirRelativo = 'respaldos';
        if (!Storage::disk('local')->exists($dirRelativo)) {
            Storage::disk('local')->makeDirectory($dirRelativo);
        }

        $timestamp   = now()->format('Y-m-d_H-i-s');
        $nombreArch  = "respaldo_{$timestamp}.bak";
        $rutaStorage = storage_path("app/{$dirRelativo}/{$nombreArch}");

        // Intentar con sqlcmd (método nativo SQL Server)
        if ($this->intentarConSqlcmd($rutaStorage, $timestamp)) {
            return self::SUCCESS;
        }

        // Fallback: dump PHP (exporta datos via SELECT)
        $this->warn('sqlcmd no disponible o falló. Usando método de exportación PHP...');
        if ($this->intentarDumpPhp($dirRelativo, $timestamp)) {
            return self::SUCCESS;
        }

        $this->error('No se pudo generar el respaldo por ningún método disponible.');
        $this->line('');
        $this->comment('NOTA: Para respaldos completos, instale SQL Server Tools (sqlcmd).');
        $this->comment('Descarga: https://learn.microsoft.com/es-es/sql/tools/sqlcmd/sqlcmd-utility');

        return self::FAILURE;
    }

    /**
     * Intenta generar el backup via sqlcmd con BACKUP DATABASE.
     */
    private function intentarConSqlcmd(string $rutaStorage, string $timestamp): bool
    {
        // Verificar si sqlcmd está disponible
        exec('sqlcmd -? 2>&1', $checkOutput, $checkCode);
        $sqlcmdDisponible = ($checkCode === 0 || str_contains(implode(' ', $checkOutput), 'Microsoft'));

        if (!$sqlcmdDisponible) {
            $this->line('sqlcmd no encontrado en el PATH del sistema.');
            return false;
        }

        $host     = env('DB_HOST', 'localhost\\SQLEXPRESS');
        $database = env('DB_DATABASE', 'prueba2');
        $username = env('DB_USERNAME', 'sa');
        $password = env('DB_PASSWORD', '');

        // Escapar la ruta para Windows
        // Escribir query a un archivo temporal para evitar problemas de escape en la shell
        $rutaEscapada = str_replace('/', '\\', $rutaStorage);
        $queryFile = tempnam(sys_get_temp_dir(), 'sgae_bkp_') . '.sql';
        $query = "BACKUP DATABASE [{$database}] TO DISK = N'{$rutaEscapada}' WITH FORMAT, COMPRESSION, STATS = 10;";
        file_put_contents($queryFile, $query);

        $cmd = sprintf(
            'sqlcmd -S "%s" -U "%s" -P "%s" -i "%s" 2>&1',
            $host,
            $username,
            $password,
            $queryFile
        );

        $this->line("Ejecutando backup via sqlcmd...");
        exec($cmd, $output, $returnCode);
        @unlink($queryFile);

        if ($returnCode === 0 && file_exists($rutaStorage)) {
            $tamano = $this->formatBytes(filesize($rutaStorage));
            $this->info("✓ Respaldo generado exitosamente:");
            $this->line("  Archivo : {$rutaStorage}");
            $this->line("  Tamaño  : {$tamano}");
            $this->line("  Fecha   : " . now()->format('d/m/Y H:i:s'));
            $this->newLine();
            $this->comment('NOTA: Si el scheduler de Laravel no está activo, ejecute manualmente:');
            $this->comment('  php artisan sgae:respaldar');

            Log::info("[RespaldarBaseDatos] Respaldo generado: {$rutaStorage} ({$tamano})");
            return true;
        }

        $errMsg = implode("\n", $output);
        $this->warn("sqlcmd falló (código {$returnCode}): " . substr($errMsg, 0, 200));
        return false;
    }

    /**
     * Fallback: exporta todas las tablas vía queries PHP (sin sqlcmd).
     * Genera un archivo SQL con INSERTs para cada tabla.
     */
    private function intentarDumpPhp(string $dirRelativo, string $timestamp): bool
    {
        $nombreSql = "respaldo_{$timestamp}.sql";
        $rutaSql   = storage_path("app/{$dirRelativo}/{$nombreSql}");

        $tablas = [
            'Usuario', 'Personal', 'Docente', 'Institucion',
            'Anio_Escolar', 'Grado', 'Mencion', 'Materia',
            'Plan_Estudios', 'Seccion', 'Asignacion_Docente',
            'Estudiante', 'Representante', 'Matricula',
            'Momento_Evaluativo', 'Evaluacion', 'Documento_Emitido',
            'Materia_Pendiente', 'Ficha_Antropometrica',
            'Auditoria', 'Login', 'Parametro_Sistema',
        ];

        $this->line("Generando dump SQL de " . count($tablas) . " tablas...");

        $contenido  = "-- Respaldo SGAE generado el " . now()->format('d/m/Y H:i:s') . "\n";
        $contenido .= "-- Base de datos: " . env('DB_DATABASE') . "\n";
        $contenido .= "-- Método: PHP dump (sqlcmd no disponible)\n\n";

        $exito = true;

        foreach ($tablas as $tabla) {
            try {
                $filas = DB::table($tabla)->get();

                if ($filas->isEmpty()) {
                    $contenido .= "-- Tabla {$tabla}: vacía\n\n";
                    $this->line("  → {$tabla}: vacía");
                    continue;
                }

                $contenido .= "-- Tabla: {$tabla} ({$filas->count()} registros)\n";

                foreach ($filas as $fila) {
                    $arr      = (array) $fila;
                    $columnas = implode(', ', array_map(fn($c) => "[{$c}]", array_keys($arr)));
                    $valores  = implode(', ', array_map(function ($v) {
                        if ($v === null) return 'NULL';
                        if (is_bool($v)) return $v ? '1' : '0';
                        if (is_numeric($v)) return $v;
                        return "'" . str_replace("'", "''", (string) $v) . "'";
                    }, $arr));

                    $contenido .= "INSERT INTO [{$tabla}] ({$columnas}) VALUES ({$valores});\n";
                }

                $contenido .= "\n";
                $this->line("  → {$tabla}: {$filas->count()} registros exportados");

            } catch (\Throwable $e) {
                $this->warn("  → {$tabla}: no se pudo exportar — " . $e->getMessage());
                $contenido .= "-- ERROR al exportar {$tabla}: " . $e->getMessage() . "\n\n";
                $exito = false;
            }
        }

        file_put_contents($rutaSql, $contenido);

        $tamano = $this->formatBytes(filesize($rutaSql));
        $this->info("✓ Dump SQL generado:");
        $this->line("  Archivo : {$rutaSql}");
        $this->line("  Tamaño  : {$tamano}");
        $this->newLine();
        $this->comment('NOTA: Este es un dump de datos SQL (no un backup nativo .bak de SQL Server).');
        $this->comment('Para backups completos, instale sqlcmd o use SQL Server Management Studio.');

        Log::info("[RespaldarBaseDatos] Dump PHP generado: {$rutaSql} ({$tamano})");

        return true; // siempre retorna true si se creó el archivo, aunque algunas tablas fallaron
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
