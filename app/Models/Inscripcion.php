<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    use HasFactory;

    protected $table = 'inscripciones'; 

    protected $fillable = [
        'estudiante_id',
        'materia_id',
        'periodo',
        'seccion',
        'fecha_inscripcion',
        'estado'
    ];
    
    protected $casts = [
        'fecha_inscripcion' => 'date',
    ];

    // Relación: Una inscripción pertenece a un estudiante
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    // Relación: Una inscripción pertenece a una materia
    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    // Relación: Una inscripción tiene una evaluación
    public function evaluacion()
    {
        return $this->hasOne(Evaluacion::class);
    }

    // Scope para calcular carga académica
    public function scopeCargaAcademica($query, $estudianteId, $periodo)
    {
        return $query->where('estudiante_id', $estudianteId)
                    ->where('periodo', $periodo)
                    ->whereIn('estado', ['inscrito', 'cursando']);
    }
}