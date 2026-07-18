<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichaAntropometrica extends Model
{
    protected $table = 'Ficha_Antropometrica';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'cedula_estudiante',
        'codigo_ano_escolar',
        'estatura',
        'peso',
        'talla_camisa',
        'talla_pantalon',
        'talla_zapatos',
        'fecha_medicion',
    ];

    protected $casts = [
        'estatura'        => 'float',
        'peso'            => 'float',
        'fecha_medicion'  => 'date',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'cedula_estudiante', 'cedula_estudiante');
    }

    public function anioEscolar()
    {
        return $this->belongsTo(AnioEscolar::class, 'codigo_ano_escolar', 'codigo_ano_escolar');
    }
}
