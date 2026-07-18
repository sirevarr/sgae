<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnioEscolar extends Model
{
    protected $table = 'Anio_Escolar';
    protected $primaryKey = 'codigo_ano_escolar';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'codigo_ano_escolar',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function secciones()
    {
        return $this->hasMany(Seccion::class, 'codigo_ano_escolar', 'codigo_ano_escolar');
    }

    public function momentos()
    {
        return $this->hasMany(MomentoEvaluativo::class, 'codigo_ano_escolar', 'codigo_ano_escolar');
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'codigo_ano_escolar', 'codigo_ano_escolar');
    }

    public function planesEstudio()
    {
        return $this->hasMany(PlanEstudios::class, 'codigo_ano_escolar', 'codigo_ano_escolar');
    }

    /** Retorna el año escolar vigente o el más reciente */
    public static function vigente(): ?self
    {
        return static::where('estado', 'vigente')->first()
            ?? static::orderByDesc('codigo_ano_escolar')->first();
    }
}
