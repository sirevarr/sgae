<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginRecord extends Model
{
    protected $table = 'Login';
    protected $primaryKey = 'id_login';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'fecha',
        'hora',
        'ip_acceso',
        'tipo_acceso',
        'exitoso',
    ];

    protected $casts = [
        'fecha'   => 'date',
        'exitoso' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
