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

    protected $appends = [
        'total_estudiantes',
        'cupos_disponibles',
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

    /** Alias para compatibilidad con frontend (serializa como asignaciones_docente) */
    public function asignacionesDocente()
    {
        return $this->asignaciones();
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
        return $this->normalizarTurnoTexto((string) $value);
    }

    public function setTurnoAttribute($value): void
    {
        $this->attributes['turno'] = $this->normalizarTurnoCodigo($value);
    }

    private function normalizarTurnoTexto(string $value): string
    {
        $val = strtolower(trim($value));

        return match ($val) {
            'm' => 'mañana',
            't' => 'tarde',
            'n' => 'nocturno',
            default => $value,
        };
    }

    private function normalizarTurnoCodigo($value): string
    {
        $val = strtolower(trim((string) $value));

        return match ($val) {
            'mañana', 'm' => 'M',
            'tarde', 't' => 'T',
            'nocturno', 'n' => 'N',
            default => substr(strtoupper((string) $value), 0, 1),
        };
    }
}
