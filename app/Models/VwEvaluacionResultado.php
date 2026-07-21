<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de solo lectura para la vista vw_Evaluacion_Resultado.
 * Devuelve el resultado (A/R) calculado directamente por la BD.
 */
class VwEvaluacionResultado extends Model
{
    protected $table = 'vw_Evaluacion_Resultado';
    public $timestamps = false;
    public $incrementing = false;

    // Vista de solo lectura — no se permite escritura
    protected $fillable = [];

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class, 'id_evaluacion', 'id_evaluacion');
    }
}
