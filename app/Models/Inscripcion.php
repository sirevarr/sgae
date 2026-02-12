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
        'grado',           
        'seccion',         
        'fecha_inscripcion',
        'estado'
    ];

    // Relaciones
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class);
    }

    // Relación singular para casos donde solo existe una evaluación por inscripción
    public function evaluacion()
    {
        return $this->hasOne(Evaluacion::class);
    }
}