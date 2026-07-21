<?php

namespace App\Models\Traits;

trait HasTableExists
{
    public static function tableExists(): bool
    {
        try {
            return app('db')->getSchemaBuilder()->hasTable((new static)->getTable());
        } catch (\Throwable $e) {
            return false;
        }
    }
}
