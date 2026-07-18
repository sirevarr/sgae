<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'Materia';
    protected $primaryKey = 'siglas';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'siglas',
        'nombre',
        'area_formacion',
    ];

    public function planesEstudio()
    {
        return $this->hasMany(PlanEstudios::class, 'siglas_materia', 'siglas');
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class, 'siglas_materia', 'siglas');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionDocente::class, 'siglas_materia', 'siglas');
    }
}
