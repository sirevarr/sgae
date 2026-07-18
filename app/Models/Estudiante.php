<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'Estudiante';
    protected $primaryKey = 'cedula_estudiante';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'cedula_estudiante',
        'tipo_documento',
        'nacionalidad',
        'nombres',
        'apellidos',
        'genero',
        'fecha_nacimiento',
        'lugar_nacimiento',
        'estado_nacimiento',
        'municipio_nacimiento',
        'direccion',
        'telefono',
        'correo',
        'condiciones_medicas',
        'medicamentos',
        'fecha_ingreso',
        'estado_estudiante',
        'fecha_retiro',
        'motivo_retiro',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso'    => 'date',
        'fecha_retiro'     => 'date',
    ];

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'cedula_estudiante', 'cedula_estudiante');
    }

    public function matriculaActual()
    {
        return $this->hasOne(Matricula::class, 'cedula_estudiante', 'cedula_estudiante')
                    ->where('estado_matricula', 'activa')
                    ->latest('fecha_matricula');
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class, 'cedula_estudiante', 'cedula_estudiante');
    }

    public function materiasPendientes()
    {
        return $this->hasMany(MateriaPendiente::class, 'cedula_estudiante', 'cedula_estudiante');
    }

    public function fichasAntropometricas()
    {
        return $this->hasMany(FichaAntropometrica::class, 'cedula_estudiante', 'cedula_estudiante');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoEmitido::class, 'cedula_estudiante', 'cedula_estudiante');
    }

    public function scopeActivos($query)
    {
        return $query->where('estado_estudiante', 'activo');
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombres', 'like', "%{$termino}%")
              ->orWhere('apellidos', 'like', "%{$termino}%")
              ->orWhere('cedula_estudiante', 'like', "%{$termino}%");
        });
    }
}