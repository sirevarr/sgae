<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        /**
         * Punto 5 — Respaldo automático diario de la base de datos.
         *
         * Para que este comando se ejecute automáticamente, el scheduler de Laravel
         * debe estar activo. Configura en el sistema operativo:
         *
         *   Windows (Task Scheduler):
         *     Acción: php C:\ruta\al\proyecto\artisan schedule:run
         *     Frecuencia: cada minuto
         *
         *   Linux/Mac (crontab):
         *     * * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
         *
         * Ejecución manual: php artisan sgae:respaldar
         */
        $schedule->command('sgae:respaldar')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
