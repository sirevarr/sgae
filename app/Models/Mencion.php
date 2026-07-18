<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mencion extends Model
{
    protected $table = 'Mencion';
    protected $primaryKey = 'id_mencion';
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'estado',
    ];

    public function secciones()
    {
        return $this->hasMany(Seccion::class, 'id_mencion', 'id_mencion');
    }

    public function planesEstudio()
    {
        return $this->hasMany(PlanEstudios::class, 'id_mencion', 'id_mencion');
    }
}
