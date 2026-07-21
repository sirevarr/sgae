<?php

namespace App\Models;

use App\Models\Traits\HasTableExists;
use Illuminate\Database\Eloquent\Model;

class Institucion extends Model
{
    protected $table = 'Institucion';
    protected $primaryKey = 'codigo_dea';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    use HasTableExists;

    protected $fillable = [
        'codigo_dea',
        'nombre',
        'direccion',
        'telefono',
        'municipio',
        'estado',
        'zona_educativa',
        'director_actual',
        'coordinador_academico',
    ];

    protected $casts = [
        'director_actual'       => 'integer',
        'coordinador_academico' => 'integer',
    ];

    public function director()
    {
        return $this->belongsTo(Personal::class, 'director_actual', 'cedula_personal');
    }

    public function coordinador()
    {
        return $this->belongsTo(Personal::class, 'coordinador_academico', 'cedula_personal');
    }
}
