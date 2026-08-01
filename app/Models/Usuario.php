<?php

namespace App\Models;

use App\Models\Traits\HasTableExists;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo de autenticaci�n que apunta a la tabla Usuario de prueba2.
 *
 * Campos usados por Laravel Auth:
 *   - Identificador:   codigo_usuario  (el usuario escribe esto para iniciar sesi�n)
 *   - Contrase�a:      clave_hash      (bcrypt o hash compatible almacenado en BD)
 *   - PK:              id_usuario
 */
class Usuario extends Authenticatable implements MustVerifyEmailContract
{
    use HasTableExists, HasFactory, MustVerifyEmailTrait, Notifiable;

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

    /** Campo que guarda la contrase�a hasheada */
    public function getAuthPassword(): string
    {
        return $this->clave_hash;
    }

    /** Laravel tests and password checks expect $user->password to work */
    public function getPasswordAttribute(): ?string
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
        'fecha_creacion'    => 'date',
        'ultimo_acceso'     => 'date',
        'cedula_personal'   => 'integer',
        'intentos_fallidos' => 'integer',
    ];

    public function hasRole(array|string $roles): bool
    {
        $userRole = strtolower(trim($this->rol ?? 'docente'));
        if ($userRole === 'administrador') {
            return true;
        }
        $roleList = is_array($roles) ? $roles : explode(',', $roles);
        foreach ($roleList as $r) {
            if ($userRole === strtolower(trim($r))) {
                return true;
            }
        }
        return false;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('administrador');
    }

    public function isControlEstudios(): bool
    {
        return $this->hasRole(['administrador', 'control_estudios']);
    }

    public function isDocente(): bool
    {
        return $this->hasRole('docente');
    }

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
        return $this->personal?->nombre_completo ?? $this->codigo_usuario;
    }

    public function getEmailForVerification(): string
    {
        return (string) ($this->email ?? '');
    }

    public function hasVerifiedEmail(): bool
    {
        return ! empty($this->email_verified_at);
    }

    public function markEmailAsVerified(): bool
    {
        if ($this->hasVerifiedEmail()) {
            return true;
        }

        $this->forceFill([
            'email_verified_at' => now(),
        ]);

        if ($this->exists) {
            return (bool) $this->save();
        }

        return true;
    }

    public function sendEmailVerificationNotification(): void
    {
        // No-op for the current project; email verification is not wired to a mailer.
    }
}
