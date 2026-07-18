<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionDocente extends Model
{
    protected $table = 'Asignacion_Docente';
    protected $primaryKey = 'id_asignacion';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'cedula_docente',
        'codigo_seccion',
        'siglas_materia',
        'id_mencion',
        'codigo_grado',
        'codigo_ano_escolar',
        'horas_asignadas',
    ];

    protected $casts = [
        'cedula_docente'    => 'integer',
        'id_mencion'        => 'integer',
        'horas_asignadas'   => 'integer',
    ];

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'cedula_docente', 'cedula_personal');
    }

    public function seccion()
    {
        return $this->belongsTo(Seccion::class, 'codigo_seccion', 'codigo_seccion');
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
}
