<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Representante extends Model
{
    protected $table = 'Representante';
    protected $primaryKey = 'cedula_representante';
    public $incrementing = false;
    protected $keyType = 'integer';
    public $timestamps = false;

    protected $fillable = [
        'cedula_representante',
        'nacionalidad',
        'nombres',
        'apellidos',
        'parentesco',
        'ocupacion',
        'direccion',
        'telefono',
        'correo',
        'es_representante_legal',
    ];

    protected $casts = [
        'cedula_representante'  => 'integer',
        'es_representante_legal' => 'boolean',
    ];

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'cedula_representante', 'cedula_representante');
    }
}
