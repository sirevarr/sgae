<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    use HasFactory;

    // Indicamos el nombre de la tabla ya que Laravel buscaría "evaluacions" por defecto
    protected $table = 'evaluaciones';

    protected $fillable = [
        'inscripcion_id',
        'nota_parcial1',
        'nota_parcial2',
        'nota_final',
        'promedio', // Ahora es fillable porque el modelo lo calculará y guardará
        'fecha',
        'observaciones'
    ];

    protected $casts = [
        'fecha' => 'date',
        'nota_parcial1' => 'decimal:2',
        'nota_parcial2' => 'decimal:2',
        'nota_final' => 'decimal:2',
        'promedio' => 'decimal:2',
    ];

    /**
     * Lógica automática para calcular el promedio antes de guardar.
     */
    protected static function booted()
    {
        static::saving(function ($evaluacion) {
            $n1 = $evaluacion->nota_parcial1 ?? 0;
            $n2 = $evaluacion->nota_parcial2 ?? 0;
            $nf = $evaluacion->nota_final ?? 0;
            
            // Calculamos el promedio automáticamente
            $evaluacion->promedio = ($n1 + $n2 + $nf) / 3;
        });
    }

    /**
     * Relación: Una evaluación pertenece a una inscripción.
     */
    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }

    /**
     * Accessor: Permite obtener el estado (Aprobado/Reprobado) dinámicamente.
     * Uso: $evaluacion->estado
     */
    public function getEstadoAttribute()
    {
        if (is_null($this->promedio)) {
            return 'Sin calificar';
        }
        // En Venezuela usualmente se aprueba con 10 o 9.5 (redondeado a 10)
        return $this->promedio >= 9.5 ? 'Aprobado' : 'Reprobado';
    }
}