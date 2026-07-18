<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaPendiente extends Model
{
    protected $table = 'Materia_Pendiente';
    protected $primaryKey = 'id_materia_pendiente';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'cedula_estudiante',
        'siglas_materia',
        'id_mencion',
        'codigo_grado',
        'codigo_ano_escolar_origen',
        'estado',
        'fecha_resolucion',
        'nota_final',
    ];

    protected $casts = [
        'id_mencion'        => 'integer',
        'nota_final'        => 'float',
        'fecha_resolucion'  => 'date',
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

    public function anioEscolarOrigen()
    {
        return $this->belongsTo(AnioEscolar::class, 'codigo_ano_escolar_origen', 'codigo_ano_escolar');
    }
}
