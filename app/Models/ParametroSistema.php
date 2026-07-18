<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ParametroSistema extends Model
{
    protected $table = 'Parametro_Sistema';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'clave',
        'valor',
    ];

    /**
     * Obtiene el valor de un parámetro con caché de 10 minutos.
     */
    public static function obtener(string $clave, mixed $default = null): mixed
    {
        return Cache::remember("param_{$clave}", 600, function () use ($clave, $default) {
            $param = static::find($clave);
            return $param ? $param->valor : $default;
        });
    }

    /**
     * Guarda o actualiza un parámetro y limpia el caché.
     */
    public static function establecer(string $clave, string $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        Cache::forget("param_{$clave}");
    }

    /** Nota mínima aprobatoria (por defecto 10, estándar venezolano) */
    public static function notaMinima(): float
    {
        return (float) static::obtener('nota_minima_aprobatoria', '10');
    }
}
