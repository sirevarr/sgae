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

    public function evaluaciones()
    {
        return $this->hasMany(
            Evaluacion::class,
            ['numero_momento', 'codigo_ano_escolar'],
            ['numero_momento', 'codigo_ano_escolar']
        );
    }
}
