<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    protected $table = 'Personal';
    protected $primaryKey = 'cedula_personal';
    public $incrementing = false;
    protected $keyType = 'integer';
    public $timestamps = false;

    protected $fillable = [
        'cedula_personal',
        'nombres',
        'apellidos',
        'cargo',
        'telefono',
        'correo',
        'fecha_nacimiento',
        'genero',
        'fecha_ingreso',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso'    => 'date',
        'cedula_personal'  => 'integer',
    ];

    public function docente()
    {
        return $this->hasOne(Docente::class, 'cedula_personal', 'cedula_personal');
    }

    public function usuario()
    {
        return $this->hasOne(Usuario::class, 'cedula_personal', 'cedula_personal');
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
