<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo de autenticación que apunta a la tabla Usuario de prueba2.
 *
 * Campos usados por Laravel Auth:
 *   - Identificador:   codigo_usuario  (el usuario escribe esto para iniciar sesión)
 *   - Contraseña:      clave_hash      (bcrypt o hash compatible almacenado en BD)
 *   - PK:              id_usuario
 */
class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table      = 'Usuario';
    protected $primaryKey = 'id_usuario';
    public $incrementing  = true;
    protected $keyType    = 'integer';
    public $timestamps    = false;

    /** Campo que identifica al usuario en el formulario de login */
    public function getAuthIdentifierName(): string
    {
        return 'id_usuario';
    }

    /** Campo que guarda la contraseña hasheada */
    public function getAuthPassword(): string
    {
        return $this->clave_hash;
    }

    protected $fillable = [
        'codigo_usuario',
        'cedula_personal',
        'rol',
        'clave_hash',
        'estado',
        'fecha_creacion',
        'ultimo_acceso',
        'intentos_fallidos',
    ];

    protected $hidden = [
        'clave_hash',
    ];

    protected $casts = [
        'fecha_creacion'   => 'date',
        'ultimo_acceso'    => 'date',
        'cedula_personal'  => 'integer',
        'intentos_fallidos' => 'integer',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'cedula_personal', 'cedula_personal');
    }

    public function logins()
    {
        return $this->hasMany(LoginRecord::class, 'id_usuario', 'id_usuario');
    }

    public function auditorias()
    {
        return $this->hasMany(Auditoria::class, 'id_usuario', 'id_usuario');
    }

    public function documentosEmitidos()
    {
        return $this->hasMany(DocumentoEmitido::class, 'id_usuario_emisor', 'id_usuario');
    }

    /** Nombre para mostrar en la UI (desde Personal) */
    public function getNombreCompletoAttribute(): string
    {
        return $this->personal ? $this->personal->nombre_completo : $this->codigo_usuario;
    }
}
