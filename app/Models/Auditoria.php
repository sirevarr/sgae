<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $table = 'Auditoria';
    protected $primaryKey = 'id_auditoria';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'tabla_afectada',
        'id_registro_afectado',
        'operacion',
        'fecha_hora',
        'valores_anteriores',
        'valores_nuevos',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
