<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    protected $table = 'Grado';
    protected $primaryKey = 'codigo_grado';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'codigo_grado',
        'nombre',
        'nivel_educativo',
        'numero_ano',
        'estado',
    ];

    protected $casts = [
        'numero_ano' => 'integer',
    ];

    public function secciones()
    {
        return $this->hasMany(Seccion::class, 'codigo_grado', 'codigo_grado');
    }

    public function planesEstudio()
    {
        return $this->hasMany(PlanEstudios::class, 'codigo_grado', 'codigo_grado');
    }
}
