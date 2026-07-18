<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoEmitido extends Model
{
    protected $table = 'Documento_Emitido';
    protected $primaryKey = 'id_documento';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'tipo_documento',
        'cedula_estudiante',
        'codigo_ano_escolar',
        'numero_momento',
        'folio',
        'id_usuario_emisor',
        'fecha_emision',
        'contenido_pdf',
    ];

    protected $casts = [
        'fecha_emision'  => 'datetime',
        'numero_momento' => 'integer',
    ];

    /** No incluir el PDF binario en respuestas JSON por defecto */
    protected $hidden = [
        'contenido_pdf',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'cedula_estudiante', 'cedula_estudiante');
    }

    public function anioEscolar()
    {
        return $this->belongsTo(AnioEscolar::class, 'codigo_ano_escolar', 'codigo_ano_escolar');
    }

    public function usuarioEmisor()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_emisor', 'id_usuario');
    }

    /**
     * Genera un folio único con el formato: TIPO-YYYYMM-XXXXX
     * Ejemplo: BOLETIN-202506-00001
     */
    public static function generarFolio(string $tipo): string
    {
        $prefijo = strtoupper(str_replace(['_', ' '], '', $tipo));
        $fecha   = now()->format('Ym');
        $ultimo  = static::where('folio', 'like', "{$prefijo}-{$fecha}-%")
                         ->max('folio');

        $secuencia = 1;
        if ($ultimo) {
            $partes    = explode('-', $ultimo);
            $secuencia = (int) end($partes) + 1;
        }

        return sprintf('%s-%s-%05d', $prefijo, $fecha, $secuencia);
    }
}
