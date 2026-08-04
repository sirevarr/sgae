<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * Punto 5 — Respaldo de base de datos.
 * Solo accesible para el rol: administrador.
 */
class RespaldoController extends Controller
{
    /**
     * POST /api/respaldos
     * Dispara el comando Artisan sgae:respaldar y retorna el resultado.
     */
    public function generar(): JsonResponse
    {
        try {
            $exitCode = Artisan::call('sgae:respaldar');
            $output   = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Respaldo generado correctamente.',
                    'detalle' => trim($output),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'El respaldo finalizó con errores.',
                'detalle' => trim($output),
            ], 500);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar el comando de respaldo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/respaldos
     * Lista los archivos de respaldo disponibles en storage/app/respaldos/.
     */
    public function index(): JsonResponse
    {
        try {
            $files = Storage::disk('local')->files('respaldos');

            $respaldos = collect($files)
                ->map(function ($file) {
                    return [
                        'nombre'  => basename($file),
                        'ruta'    => $file,
                        'tamaño'  => Storage::disk('local')->size($file),
                        'fecha'   => date('Y-m-d H:i:s', Storage::disk('local')->lastModified($file)),
                    ];
                })
                ->sortByDesc('fecha')
                ->values();

            return response()->json([
                'success'   => true,
                'respaldos' => $respaldos,
                'total'     => $respaldos->count(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success'   => true,
                'respaldos' => [],
                'total'     => 0,
                'nota'      => 'No se pudo leer el directorio de respaldos: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * GET /api/respaldos/{archivo}
     * Descarga el archivo de respaldo físico.
     */
    public function descargar(string $archivo)
    {
        $path = 'respaldos/' . $archivo;
        
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'El archivo de respaldo no existe.');
        }

        return Storage::disk('local')->download($path);
    }
}
