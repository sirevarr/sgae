<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_materia',
        'nombre',
        'descripcion',
        'creditos',
        'estado'
    ];

    // Relación: Una materia tiene muchas inscripciones
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }

    // Scope para filtrar por estado
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }
}
