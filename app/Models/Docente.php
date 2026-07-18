<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    protected $table = 'Docente';
    protected $primaryKey = 'cedula_personal';
    public $incrementing = false;
    protected $keyType = 'integer';
    public $timestamps = false;

    protected $fillable = [
        'cedula_personal',
        'especialidad',
        'turno',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'cedula_personal', 'cedula_personal');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionDocente::class, 'cedula_docente', 'cedula_personal');
    }

    public function secciones_guia()
    {
        return $this->hasMany(Seccion::class, 'cedula_docente_guia', 'cedula_personal');
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class, 'cedula_docente_evaluador', 'cedula_personal');
    }

    /** Nombre completo a través de Personal */
    public function getNombreCompletoAttribute(): string
    {
        return $this->personal ? $this->personal->nombre_completo : '';
    }
}
