<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    protected $table = 'Evaluacion';
    protected $primaryKey = 'id_evaluacion';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'cedula_estudiante',
        'siglas_materia',
        'id_mencion',
        'codigo_grado',
        'codigo_ano_escolar',
        'numero_momento',
        'nota',
        'fecha_evaluacion',
        'es_revision',
        'cedula_docente_evaluador',
        'fecha_modificacion',
        'motivo_modificacion',
    ];

    protected $casts = [
        'nota'                    => 'float',
        'es_revision'             => 'boolean',
        'fecha_evaluacion'        => 'date',
        'fecha_modificacion'      => 'date',
        'id_mencion'              => 'integer',
        'numero_momento'          => 'integer',
        'cedula_docente_evaluador' => 'integer',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'cedula_estudiante', 'cedula_estudiante');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'siglas_materia', 'siglas');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'codigo_grado', 'codigo_grado');
    }

    public function anioEscolar()
    {
        return $this->belongsTo(AnioEscolar::class, 'codigo_ano_escolar', 'codigo_ano_escolar');
    }

    public function docenteEvaluador()
    {
        return $this->belongsTo(Docente::class, 'cedula_docente_evaluador', 'cedula_personal');
    }

    /**
     * Resultado según tipo de evaluación y nota mínima del sistema.
     * A = Aprobado, R = Reprobado, P = Pendiente (sin nota)
     */
    public function getResultadoAttribute(): string
    {
        if ($this->nota === null) {
            return 'P';
        }
        $planEstudio = PlanEstudios::where([
            'siglas_materia'    => $this->siglas_materia,
            'id_mencion'        => $this->id_mencion,
            'codigo_grado'      => $this->codigo_grado,
            'codigo_ano_escolar' => $this->codigo_ano_escolar,
        ])->first();

        if ($planEstudio && $planEstudio->tipo_evaluacion === 'L') {
            return $this->nota == 1 ? 'A' : 'R';
        }

        return $this->nota >= ParametroSistema::notaMinima() ? 'A' : 'R';
    }
}