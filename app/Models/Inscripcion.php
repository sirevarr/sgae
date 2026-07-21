<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    protected $table = 'Matricula';
    protected $primaryKey = 'id_matricula';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'cedula_estudiante',
        'codigo_ano_escolar',
        'codigo_seccion',
        'cedula_representante',
        'fecha_matricula',
        'numero_lista',
        'condicion_ingreso',
        'procedencia',
        'ano_inicio_cursante',
        'estado_matricula',
        'observaciones',
        'fecha_retiro',
        'motivo_retiro',
    ];

    protected $casts = [
        'fecha_matricula'      => 'date',
        'fecha_retiro'         => 'date',
        'numero_lista'         => 'integer',
        'ano_inicio_cursante'  => 'integer',
        'cedula_representante' => 'integer',
    ];

    protected $appends = [
        'periodo',
        'fecha_inscripcion',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'cedula_estudiante', 'cedula_estudiante');
    }

    public function seccion()
    {
        return $this->belongsTo(Seccion::class, 'codigo_seccion', 'codigo_seccion');
    }

    public function anioEscolar()
    {
        return $this->belongsTo(AnioEscolar::class, 'codigo_ano_escolar', 'codigo_ano_escolar');
    }

    public function representante()
    {
        return $this->belongsTo(Representante::class, 'cedula_representante', 'cedula_representante');
    }

    public function getPeriodoAttribute(): ?string
    {
        return $this->codigo_ano_escolar;
    }

    public function getFechaInscripcionAttribute(): ?string
    {
        return $this->fecha_matricula?->toDateString();
    }
}
