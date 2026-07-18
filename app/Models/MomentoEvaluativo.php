<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MomentoEvaluativo extends Model
{
    protected $table = 'Momento_Evaluativo';
    // PK compuesta: (numero_momento, codigo_ano_escolar)
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'numero_momento',
        'codigo_ano_escolar',
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'porcentaje',
        'estado',
    ];

    protected $casts = [
        'numero_momento'  => 'integer',
        'fecha_inicio'    => 'date',
        'fecha_fin'       => 'date',
        'porcentaje'      => 'float',
    ];

    public function anioEscolar()
    {
        return $this->belongsTo(AnioEscolar::class, 'codigo_ano_escolar', 'codigo_ano_escolar');
    }

    /**
     * Obtiene las evaluaciones de este momento evaluativo.
     * Laravel no soporta claves compuestas en hasMany, por eso usamos un scope.
     */
    public function scopeConEvaluaciones($query)
    {
        // Usa este scope si necesitas filtrar por momento: Model::conEvaluaciones()->...
        return $query;
    }

    /**
     * Devuelve la colección de evaluaciones para este momento.
     * Uso: $momento->getEvaluaciones()
     */
    public function getEvaluaciones()
    {
        return Evaluacion::where('numero_momento', $this->numero_momento)
            ->where('codigo_ano_escolar', $this->codigo_ano_escolar)
            ->get();
    }
}
