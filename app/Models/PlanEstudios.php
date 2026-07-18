<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanEstudios extends Model
{
    protected $table = 'Plan_Estudios';
    // PK compuesta: siglas_materia, id_mencion, codigo_grado, codigo_ano_escolar
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'siglas_materia',
        'id_mencion',
        'codigo_grado',
        'codigo_ano_escolar',
        'horas_semanales',
        'obligatoria',
        'tipo_evaluacion',
        'se_repara',
        'creditos',
        'estado',
    ];

    protected $casts = [
        'obligatoria'      => 'boolean',
        'se_repara'        => 'boolean',
        'horas_semanales'  => 'integer',
        'creditos'         => 'integer',
        'id_mencion'       => 'integer',
    ];

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'siglas_materia', 'siglas');
    }

    public function mencion()
    {
        return $this->belongsTo(Mencion::class, 'id_mencion', 'id_mencion');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'codigo_grado', 'codigo_grado');
    }

    public function anioEscolar()
    {
        return $this->belongsTo(AnioEscolar::class, 'codigo_ano_escolar', 'codigo_ano_escolar');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionDocente::class, 'siglas_materia', 'siglas_materia');
    }
}
