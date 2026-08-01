<?php

namespace App\Models\Traits;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;

/**
 * Trait Auditable
 *
 * Proporciona auditoría de modificaciones sobre entidades sensibles del sistema.
 * Registra operaciones I (insert), U (update) y D (delete) en la tabla Auditoria.
 */
trait Auditable
{
    /**
     * Registrar una operación en la tabla de auditoría.
     *
     * @param  string      $tabla             Nombre de la tabla afectada (e.g. 'Evaluacion')
     * @param  string      $idRegistro        PK del registro afectado (como string)
     * @param  string      $operacion         'I' = insert, 'U' = update, 'D' = delete
     * @param  array|null  $valoresAnteriores Valores antes del cambio (null en inserts)
     * @param  array|null  $valoresNuevos     Valores después del cambio (null en deletes)
     */
    public static function registrarAuditoria(
        string $tabla,
        string $idRegistro,
        string $operacion,
        ?array $valoresAnteriores = null,
        ?array $valoresNuevos = null
    ): void {
        try {
            $ip = request()->ip() ?? '127.0.0.1';

            if ($valoresNuevos !== null) {
                $valoresNuevos['_ip'] = $ip;
            } elseif ($valoresAnteriores !== null) {
                $valoresAnteriores['_ip'] = $ip;
            }

            Auditoria::create([
                'id_usuario'            => Auth::id(),
                'tabla_afectada'        => $tabla,
                'id_registro_afectado'  => (string) $idRegistro,
                'operacion'             => strtoupper(substr($operacion, 0, 1)),
                'fecha_hora'            => now(),
                'valores_anteriores'    => $valoresAnteriores ? json_encode($valoresAnteriores, JSON_UNESCAPED_UNICODE) : null,
                'valores_nuevos'        => $valoresNuevos    ? json_encode($valoresNuevos,    JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                "[Auditable] No se pudo registrar auditoría en {$tabla} ({$operacion}): " . $e->getMessage()
            );
        }
    }
}
