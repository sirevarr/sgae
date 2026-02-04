<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    use HasFactory;

    protected $fillable = [
        'cedula', 
        'nombres', 
        'apellidos', 
        'genero', 
        'fecha_nacimiento', 
        'lugar_nacimiento', // <--- Añadir
        'direccion',        // <--- Añadir
        'email', 
        'telefono',         // <--- Añadir
        'estado'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where('nombres', 'like', "%{$termino}%")
                    ->orWhere('apellidos', 'like', "%{$termino}%")
                    ->orWhere('cedula', 'like', "%{$termino}%");
    }

    public function getNombreCompletoAttribute()
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}