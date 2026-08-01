<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentoEmitido;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentoController extends Controller
{
    public function __construct(private PdfService $pdf) {}

    /** Listar documentos emitidos (con filtros opcionales) */
    public function index(Request $request): JsonResponse
    {
        $q = $this->buildIndexQuery($request)
            ->orderByDesc('fecha_emision')
            ->paginate(20);

        return response()->json($q);
    }

    /**
     * Generar y descargar boletín de calificaciones.
     * GET /api/documentos/boletin/{cedula}/{anio}?momento=1
     */
    public function boletin(Request $request, string $cedula, string $anio)
    {
        $momento = $request->integer('momento', 0) ?: null;
        $this->registrarEmision('boletin', $cedula, $anio, $momento);
        return $this->pdf->boletin($cedula, $anio, $momento);
    }

    /**
     * Generar y descargar constancia de estudio.
     * GET /api/documentos/constancia-estudio/{cedula}/{anio}?motivo=...
     */
    public function constanciaEstudio(Request $request, string $cedula, string $anio)
    {
        $motivo = $request->string('motivo', '')->toString();
        $this->registrarEmision('constancia', $cedula, $anio);
        return $this->pdf->constanciaEstudio($cedula, $anio, $motivo);
    }

    /**
     * Generar y descargar constancia de buena conducta.
     * GET /api/documentos/constancia-conducta/{cedula}/{anio}
     */
    public function constanciaConducta(string $cedula, string $anio)
    {
        $this->registrarEmision('constancia', $cedula, $anio);
        return $this->pdf->constanciaConducta($cedula, $anio);
    }

    /**
     * Generar constancia de prosecución.
     * GET /api/documentos/constancia-prosecucion/{cedula}/{anio}
     */
    public function constanciaProsecucion(string $cedula, string $anio)
    {
        $this->registrarEmision('constancia', $cedula, $anio);
        return $this->pdf->constanciaProsecucion($cedula, $anio);
    }

    /**
     * Generar constancia de asistencia.
     * GET /api/documentos/constancia-asistencia/{cedula}/{anio}
     */
    public function constanciaAsistencia(string $cedula, string $anio)
    {
        $this->registrarEmision('constancia', $cedula, $anio);
        return $this->pdf->constanciaAsistencia($cedula, $anio);
    }

    /**
     * Generar lista de sección.
     * GET /api/documentos/lista-seccion/{seccion}/{anio}
     */
    public function listaSeccion(string $seccion, string $anio)
    {
        return $this->pdf->listaSeccion($seccion, $anio);
    }

    /**
     * Generar resumen de calificaciones por sección (libro de calificaciones).
     * GET /api/documentos/resumen-seccion/{seccion}/{anio}?momento=1
     */
    public function resumenSeccion(Request $request, string $seccion, string $anio)
    {
        $momento = $request->integer('momento', 0) ?: null;
        return $this->pdf->resumenSeccion($seccion, $anio, $momento);
    }

    /** Eliminar un documento emitido */
    public function destroy(int $id): JsonResponse
    {
        $doc = DocumentoEmitido::findOrFail($id);
        try {
            $doc->delete();
            return response()->json(null, 204);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['error' => 'No se puede eliminar el documento porque tiene dependencias.'], 409);
        }
    }

    /** Registrar emisión en tabla Documento_Emitido */
    private function registrarEmision(string $tipo, string $cedula, string $anio, ?int $momento = null): void
    {
        try {
            DocumentoEmitido::create([
                'tipo_documento'     => $tipo,
                'cedula_estudiante'  => $cedula,
                'codigo_ano_escolar' => $anio,
                'numero_momento'     => $momento,
                'folio'              => DocumentoEmitido::generarFolio($tipo),
                'id_usuario_emisor'  => Auth::id(),
                'fecha_emision'      => now(),
                'contenido_pdf'      => \Illuminate\Support\Facades\DB::raw('0x'),
            ]);
        } catch (\Throwable $e) {
            \Log::warning("No se pudo registrar emisión de {$tipo}: " . $e->getMessage());
        }
    }

    private function buildIndexQuery(Request $request)
    {
        return DocumentoEmitido::with(['estudiante', 'anioEscolar', 'usuarioEmisor.personal'])
            ->when($request->filled('cedula_estudiante'), fn ($query) =>
                $query->where('cedula_estudiante', $request->cedula_estudiante)
            )
            ->when($request->filled('tipo_documento'), fn ($query) =>
                $query->where('tipo_documento', $request->tipo_documento)
            )
            ->when($request->filled('codigo_ano_escolar'), fn ($query) =>
                $query->where('codigo_ano_escolar', $request->codigo_ano_escolar)
            );
    }
}
