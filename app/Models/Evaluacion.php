<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones';

    protected $fillable = [
        'inscripcion_id',
        'nota_parcial1',
        'nota_parcial2',
        'nota_final',
        'promedio',
        'estado',
        'fecha',
        'observaciones' // <--- Añadir este campo
    ];

    // Relación con Inscripción
    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    // LÓGICA AUTOMÁTICA: Se ejecuta antes de guardar en la BD
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $p1 = $model->nota_parcial1 ?? 0;
            $p2 = $model->nota_parcial2 ?? 0;
            $nf = $model->nota_final ?? 0;

            // Calculamos promedio (Escala 0-20)
            $model->promedio = round(($p1 + $p2 + $nf) / 3, 2);

            // Definimos estado automáticamente
            $model->estado = $model->promedio >= 10 ? 'aprobado' : 'reprobado';
        });
    }
}