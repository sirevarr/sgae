<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seccion extends Model
{
    protected $table = 'Seccion';
    protected $primaryKey = 'codigo_seccion';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'codigo_seccion',
        'letra',
        'codigo_grado',
        'codigo_ano_escolar',
        'id_mencion',
        'cedula_docente_guia',
        'capacidad_maxima',
        'turno',
        'aula_asignada',
    ];

    protected $casts = [
        'capacidad_maxima'   => 'integer',
        'id_mencion'         => 'integer',
        'cedula_docente_guia' => 'integer',
    ];

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'codigo_grado', 'codigo_grado');
    }

    public function anioEscolar()
    {
        return $this->belongsTo(AnioEscolar::class, 'codigo_ano_escolar', 'codigo_ano_escolar');
    }

    public function mencion()
    {
        return $this->belongsTo(Mencion::class, 'id_mencion', 'id_mencion');
    }

    public function docenteGuia()
    {
        return $this->belongsTo(Docente::class, 'cedula_docente_guia', 'cedula_personal');
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'codigo_seccion', 'codigo_seccion');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionDocente::class, 'codigo_seccion', 'codigo_seccion');
    }

    /** Total de estudiantes activos en la sección */
    public function getTotalEstudiantesAttribute(): int
    {
        return $this->matriculas()
            ->where('estado_matricula', 'activa')
            ->count();
    }

    /** Cupos disponibles */
    public function getCuposDisponiblesAttribute(): int
    {
        $max = $this->capacidad_maxima ?? 0;
        return max(0, $max - $this->total_estudiantes);
    }

    public function getTurnoAttribute($value): string
    {
        $val = strtolower(trim($value));
        if ($val === 'm') return 'mañana';
        if ($val === 't') return 'tarde';
        if ($val === 'n') return 'nocturno';
        return $value;
    }

    public function setTurnoAttribute($value): void
    {
        $val = strtolower(trim($value));
        if ($val === 'mañana' || $val === 'm') {
            $this->attributes['turno'] = 'M';
        } elseif ($val === 'tarde' || $val === 't') {
            $this->attributes['turno'] = 'T';
        } elseif ($val === 'nocturno' || $val === 'n') {
            $this->attributes['turno'] = 'N';
        } else {
            $this->attributes['turno'] = substr(strtoupper($value), 0, 1);
        }
    }
}
