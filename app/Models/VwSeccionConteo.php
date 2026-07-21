<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de solo lectura para la vista vw_Seccion_Conteo.
 * Devuelve el conteo de estudiantes por sección (total, varones, hembras).
 */
class VwSeccionConteo extends Model
{
    protected $table = 'vw_Seccion_Conteo';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'codigo_seccion';
    protected $keyType = 'string';

    // Vista de solo lectura
    protected $fillable = [];

    public function seccion()
    {
        return $this->belongsTo(Seccion::class, 'codigo_seccion', 'codigo_seccion');
    }
}
