<?php
require 'c:/Users/sires/OneDrive/Desktop/FALTANTES/programateg/sgae-proyecto/vendor/autoload.php';
$app = require_once 'c:/Users/sires/OneDrive/Desktop/FALTANTES/programateg/sgae-proyecto/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
foreach ($tables as $table) {
    $t = $table->TABLE_NAME;
    echo "\n--- TABLE: $t ---\n";
    $columns = DB::select("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$t' ORDER BY ORDINAL_POSITION");
    foreach ($columns as $c) {
        $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
        echo "  {$c->COLUMN_NAME}: {$c->DATA_TYPE}{$len} " . ($c->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
}
