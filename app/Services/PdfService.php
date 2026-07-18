<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\Evaluacion;
use App\Models\Institucion;
use App\Models\Matricula;
use App\Models\PlanEstudios;
use App\Models\ParametroSistema;
use App\Models\AnioEscolar;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Servicio de generación de documentos PDF para SGAE.
 * Sigue el formato institucional del MPPE (encabezado, cuerpo, firma).
 */
class PdfService
{
    // ─────────────────────────────────────────────
    //  BOLETÍN DE CALIFICACIONES
    // ─────────────────────────────────────────────

    /**
     * Genera el boletín de calificaciones de un estudiante para un año escolar.
     *
     * @param string $cedula_estudiante
     * @param string $codigo_ano_escolar
     * @param int|null $numero_momento null = boletín final (3 momentos)
     */
    public function boletin(string $cedula_estudiante, string $codigo_ano_escolar, ?int $numero_momento = null): \Illuminate\Http\Response
    {
        $estudiante = Estudiante::findOrFail($cedula_estudiante);
        $anio       = AnioEscolar::findOrFail($codigo_ano_escolar);
        $institucion = Institucion::with(['director', 'coordinador'])->first();

        $matricula = Matricula::activa()
            ->where('cedula_estudiante', $cedula_estudiante)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->with(['seccion.grado', 'seccion.mencion'])
            ->first();

        if (!$matricula) {
            abort(404, 'El estudiante no está matriculado en este año escolar.');
        }

        $seccion = $matricula->seccion;

        $plan = PlanEstudios::with('materia')
            ->where('codigo_grado', $seccion->codigo_grado)
            ->where('id_mencion', $seccion->id_mencion)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->get();

        $evalQuery = Evaluacion::where('cedula_estudiante', $cedula_estudiante)
            ->where('codigo_ano_escolar', $codigo_ano_escolar);

        $evaluaciones = $evalQuery->get()->groupBy('siglas_materia');

        $notaMinima = ParametroSistema::notaMinima();
        $data = [
            'institucion'    => $institucion,
            'estudiante'     => $estudiante,
            'anio'           => $anio,
            'matricula'      => $matricula,
            'seccion'        => $seccion,
            'plan'           => $plan,
            'evaluaciones'   => $evaluaciones,
            'nota_minima'    => $notaMinima,
            'numero_momento' => $numero_momento,
            'tipo_boletin'   => $numero_momento ? "Momento {$numero_momento}" : 'Final',
            'fecha_emision'  => now()->format('d/m/Y'),
        ];

        return Pdf::loadView('pdf.boletin', $data)
            ->setPaper('letter', 'portrait')
            ->download("boletin_{$cedula_estudiante}_{$codigo_ano_escolar}.pdf");
    }

    // ─────────────────────────────────────────────
    //  CONSTANCIA DE ESTUDIO
    // ─────────────────────────────────────────────

    /**
     * Genera una constancia de estudio al estilo del MPPE venezolano.
     */
    public function constanciaEstudio(string $cedula_estudiante, string $codigo_ano_escolar, string $motivo = ''): \Illuminate\Http\Response
    {
        $estudiante  = Estudiante::findOrFail($cedula_estudiante);
        $anio        = AnioEscolar::findOrFail($codigo_ano_escolar);
        $institucion = Institucion::with(['director', 'coordinador'])->first();

        $matricula = Matricula::activa()
            ->where('cedula_estudiante', $cedula_estudiante)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->with(['seccion.grado', 'seccion.mencion', 'representante'])
            ->first();

        if (!$matricula) {
            abort(404, 'El estudiante no está matriculado en este año escolar.');
        }

        $data = [
            'institucion'   => $institucion,
            'estudiante'    => $estudiante,
            'anio'          => $anio,
            'matricula'     => $matricula,
            'seccion'       => $matricula->seccion,
            'motivo'        => $motivo ?: 'los fines que el solicitante estime convenientes',
            'fecha_emision' => now()->format('d \d\e F \d\e Y'),
            'folio'         => \App\Models\DocumentoEmitido::generarFolio('CONSTANCIA'),
        ];

        return Pdf::loadView('pdf.constancia_estudio', $data)
            ->setPaper('letter', 'portrait')
            ->download("constancia_{$cedula_estudiante}.pdf");
    }

    // ─────────────────────────────────────────────
    //  CONSTANCIA DE BUENA CONDUCTA
    // ─────────────────────────────────────────────

    public function constanciaConducta(string $cedula_estudiante, string $codigo_ano_escolar): \Illuminate\Http\Response
    {
        $estudiante  = Estudiante::findOrFail($cedula_estudiante);
        $anio        = AnioEscolar::findOrFail($codigo_ano_escolar);
        $institucion = Institucion::with(['director', 'coordinador'])->first();

        $matricula = Matricula::activa()
            ->where('cedula_estudiante', $cedula_estudiante)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->with(['seccion.grado', 'seccion.mencion'])
            ->first();

        if (!$matricula) {
            abort(404, 'El estudiante no está matriculado en este año escolar.');
        }

        $data = [
            'institucion'   => $institucion,
            'estudiante'    => $estudiante,
            'anio'          => $anio,
            'matricula'     => $matricula,
            'seccion'       => $matricula->seccion,
            'fecha_emision' => now()->format('d \d\e F \d\e Y'),
            'folio'         => \App\Models\DocumentoEmitido::generarFolio('CONDUCTA'),
        ];

        return Pdf::loadView('pdf.constancia_conducta', $data)
            ->setPaper('letter', 'portrait')
            ->download("buena_conducta_{$cedula_estudiante}.pdf");
    }

    // ─────────────────────────────────────────────
    //  LISTA DE SECCIÓN (CONTROL DEL DOCENTE)
    // ─────────────────────────────────────────────

    public function listaSeccion(string $codigo_seccion, string $codigo_ano_escolar): \Illuminate\Http\Response
    {
        $seccion     = \App\Models\Seccion::with(['grado', 'mencion', 'docenteGuia.personal'])->findOrFail($codigo_seccion);
        $anio        = AnioEscolar::findOrFail($codigo_ano_escolar);
        $institucion = Institucion::with(['director'])->first();

        $matriculas = Matricula::activa()
            ->where('codigo_seccion', $codigo_seccion)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->with('estudiante')
            ->orderBy('numero_lista')
            ->get();

        $data = [
            'institucion' => $institucion,
            'seccion'     => $seccion,
            'anio'        => $anio,
            'matriculas'  => $matriculas,
            'fecha_hoy'   => now()->format('d/m/Y'),
        ];

        return Pdf::loadView('pdf.lista_seccion', $data)
            ->setPaper('letter', 'landscape')
            ->download("lista_{$codigo_seccion}.pdf");
    }

    // ─────────────────────────────────────────────
    //  GENERAR PDF COMO BYTES (para guardar en BD)
    // ─────────────────────────────────────────────

    /**
     * Genera un PDF y retorna su contenido binario (para guardar en Documento_Emitido).
     */
    public function generarBytes(string $vista, array $data, string $orientation = 'portrait'): string
    {
        return Pdf::loadView($vista, $data)
            ->setPaper('letter', $orientation)
            ->output();
    }
}
