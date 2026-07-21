<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('migrations')) {
            Schema::create('migrations', function (Blueprint $table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
            });
        }

        $migrationsPath = database_path('migrations');
        $files = glob($migrationsPath . DIRECTORY_SEPARATOR . '*.php');
        $batch = 1;

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $exists = DB::table('migrations')->where('migration', $name)->exists();
            if (! $exists) {
                DB::table('migrations')->insert([
                    'migration' => $name,
                    'batch' => $batch,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Do not drop migrations table to avoid losing migration history.
    }
};
