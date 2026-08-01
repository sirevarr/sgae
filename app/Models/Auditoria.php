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

    protected $appends = [
        'ip_usuario',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Accesor para obtener la dirección IP asociada a la auditoría.
     * Busca la IP almacenada en la carga JSON de la auditoría o, en su defecto,
     * la IP registrada en el último login del usuario.
     */
    public function getIpUsuarioAttribute(): string
    {
        if (!empty($this->valores_nuevos)) {
            $data = json_decode($this->valores_nuevos, true);
            if (is_array($data) && isset($data['_ip']) && !empty($data['_ip'])) {
                return $data['_ip'];
            }
        }

        if (!empty($this->valores_anteriores)) {
            $data = json_decode($this->valores_anteriores, true);
            if (is_array($data) && isset($data['_ip']) && !empty($data['_ip'])) {
                return $data['_ip'];
            }
        }

        if ($this->id_usuario) {
            $login = LoginRecord::where('id_usuario', $this->id_usuario)
                ->whereNotNull('ip_acceso')
                ->orderByDesc('id_login')
                ->first();
            if ($login && !empty($login->ip_acceso)) {
                return $login->ip_acceso;
            }
        }

        return '127.0.0.1';
    }
}
