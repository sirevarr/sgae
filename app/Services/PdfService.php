<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\Evaluacion;
use App\Models\Institucion;
use App\Models\Matricula;
use App\Models\Materia;
use App\Models\PlanEstudios;
use App\Models\ParametroSistema;
use App\Models\AnioEscolar;
use App\Models\Seccion;
use App\Models\FichaAntropometrica;
use App\Models\MateriaPendiente;
use App\Models\VwSeccionConteo;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Servicio de generación de documentos PDF para SGAE.
 * Sigue el formato institucional del MPPE (encabezado triple, cuerpo, firma).
 * Compatible con los formatos de Gesman.
 */
class PdfService
{
    private const MSG_ESTUDIANTE_NO_MATRICULADO = 'El estudiante no está matriculado en este año escolar.';

    private function cargarInstitucion(array $relations = ['director', 'coordinador']): ?Institucion
    {
        return Institucion::with($relations)->first();
    }

    private function buscarMatriculaEstudiante(string $cedula_estudiante, string $codigo_ano_escolar, array $relations = []): ?Matricula
    {
        $query = Matricula::activa()
            ->where('cedula_estudiante', $cedula_estudiante)
            ->where('codigo_ano_escolar', $codigo_ano_escolar);

        if ($relations !== []) {
            $query->with($relations);
        }

        return $query->first();
    }

    private function abortSiSinMatricula(?Matricula $matricula): void
    {
        if (!$matricula) {
            abort(404, self::MSG_ESTUDIANTE_NO_MATRICULADO);
        }
    }

    // ─────────────────────────────────────────────
    //  BOLETÍN DE CALIFICACIONES
    // ─────────────────────────────────────────────

    /**
     * Genera el boletín de calificaciones de un estudiante para un año escolar.
     * Incluye ficha antropométrica, materias pendientes y observaciones.
     */
    public function boletin(string $cedula_estudiante, string $codigo_ano_escolar, ?int $numero_momento = null): \Illuminate\Http\Response
    {
        $estudiante  = Estudiante::findOrFail($cedula_estudiante);
        $anio        = AnioEscolar::findOrFail($codigo_ano_escolar);
        $institucion = $this->cargarInstitucion(['director', 'coordinador']);

        $matricula = $this->buscarMatriculaEstudiante(
            $cedula_estudiante,
            $codigo_ano_escolar,
            ['seccion.grado', 'seccion.mencion', 'representante']
        );

        $this->abortSiSinMatricula($matricula);

        $seccion = $matricula->seccion;

        $plan = PlanEstudios::with('materia')
            ->where('codigo_grado', $seccion->codigo_grado)
            ->where('id_mencion', $seccion->id_mencion)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->get();

        $evaluaciones = Evaluacion::where('cedula_estudiante', $cedula_estudiante)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->get()
            ->groupBy('siglas_materia');

        // Ficha Antropométrica del año escolar
        $ficha = FichaAntropometrica::where('cedula_estudiante', $cedula_estudiante)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->first();

        // Materias pendientes del estudiante
        $pendientes = MateriaPendiente::with('materia')
            ->where('cedula_estudiante', $cedula_estudiante)
            ->get();

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
            'ficha'          => $ficha,
            'pendientes'     => $pendientes,
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
        $institucion = $this->cargarInstitucion(['director', 'coordinador']);

        $matricula = $this->buscarMatriculaEstudiante(
            $cedula_estudiante,
            $codigo_ano_escolar,
            ['seccion.grado', 'seccion.mencion', 'representante']
        );

        $this->abortSiSinMatricula($matricula);

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
        $institucion = $this->cargarInstitucion(['director', 'coordinador']);

        $matricula = $this->buscarMatriculaEstudiante(
            $cedula_estudiante,
            $codigo_ano_escolar,
            ['seccion.grado', 'seccion.mencion']
        );

        $this->abortSiSinMatricula($matricula);

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
    //  CONSTANCIA DE PROSECUCIÓN
    // ─────────────────────────────────────────────

    /**
     * Genera constancia de prosecución indicando grado cursado y grado promovido.
     * Incluye tabla de doble firma (validez nacional/internacional) estilo Gesman.
     */
    public function constanciaProsecucion(string $cedula_estudiante, string $codigo_ano_escolar): \Illuminate\Http\Response
    {
        $estudiante  = Estudiante::findOrFail($cedula_estudiante);
        $anio        = AnioEscolar::findOrFail($codigo_ano_escolar);
        $institucion = $this->cargarInstitucion(['director']);

        $matricula = $this->buscarMatriculaEstudiante(
            $cedula_estudiante,
            $codigo_ano_escolar,
            ['seccion.grado', 'seccion.mencion']
        );

        $this->abortSiSinMatricula($matricula);

        $data = [
            'institucion'   => $institucion,
            'estudiante'    => $estudiante,
            'anio'          => $anio,
            'matricula'     => $matricula,
            'seccion'       => $matricula->seccion,
            'fecha_emision' => now()->format('d \d\e F \d\e Y'),
            'folio'         => \App\Models\DocumentoEmitido::generarFolio('PROSECUCION'),
        ];

        return Pdf::loadView('pdf.constancia_prosecucion', $data)
            ->setPaper('letter', 'portrait')
            ->download("prosecucion_{$cedula_estudiante}.pdf");
    }

    // ─────────────────────────────────────────────
    //  CONSTANCIA DE ASISTENCIA
    // ─────────────────────────────────────────────

    /**
     * Genera constancia de asistencia regular del estudiante.
     */
    public function constanciaAsistencia(string $cedula_estudiante, string $codigo_ano_escolar): \Illuminate\Http\Response
    {
        $estudiante  = Estudiante::findOrFail($cedula_estudiante);
        $anio        = AnioEscolar::findOrFail($codigo_ano_escolar);
        $institucion = $this->cargarInstitucion(['director']);

        $matricula = $this->buscarMatriculaEstudiante(
            $cedula_estudiante,
            $codigo_ano_escolar,
            ['seccion.grado', 'seccion.mencion']
        );

        $this->abortSiSinMatricula($matricula);

        $data = [
            'institucion'   => $institucion,
            'estudiante'    => $estudiante,
            'anio'          => $anio,
            'matricula'     => $matricula,
            'seccion'       => $matricula->seccion,
            'fecha_emision' => now()->format('d \d\e F \d\e Y'),
            'folio'         => \App\Models\DocumentoEmitido::generarFolio('ASISTENCIA'),
        ];

        return Pdf::loadView('pdf.constancia_asistencia', $data)
            ->setPaper('letter', 'portrait')
            ->download("asistencia_{$cedula_estudiante}.pdf");
    }

    // ─────────────────────────────────────────────
    //  LISTA DE SECCIÓN
    // ─────────────────────────────────────────────

    public function listaSeccion(string $codigo_seccion, string $codigo_ano_escolar): \Illuminate\Http\Response
    {
        $seccion     = Seccion::with(['grado', 'mencion', 'docenteGuia.personal'])->findOrFail($codigo_seccion);
        $anio        = AnioEscolar::findOrFail($codigo_ano_escolar);
        $institucion = $this->cargarInstitucion(['director']);

        $matriculas = Matricula::activa()
            ->where('codigo_seccion', $codigo_seccion)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->with('estudiante')
            ->orderBy('numero_lista')
            ->get();

        // Datos de la vista vw_Seccion_Conteo
        $conteoVista = VwSeccionConteo::find($codigo_seccion);

        $data = [
            'institucion'   => $institucion,
            'seccion'       => $seccion,
            'anio'          => $anio,
            'matriculas'    => $matriculas,
            'conteoVista'   => $conteoVista,
            'fecha_hoy'     => now()->format('d/m/Y'),
        ];

        return Pdf::loadView('pdf.lista_seccion', $data)
            ->setPaper('letter', 'landscape')
            ->download("lista_{$codigo_seccion}.pdf");
    }

    // ─────────────────────────────────────────────
    //  RESUMEN DE SECCIÓN (LIBRO DE CALIFICACIONES)
    // ─────────────────────────────────────────────

    /**
     * Genera el resumen de calificaciones de toda la sección por momento.
     * Orientación landscape, tipo "libro de calificaciones" estilo Gesman.
     */
    public function resumenSeccion(string $codigo_seccion, string $codigo_ano_escolar, ?int $numero_momento = null): \Illuminate\Http\Response
    {
        $seccion     = Seccion::with(['grado', 'mencion', 'docenteGuia.personal'])->findOrFail($codigo_seccion);
        $anio        = AnioEscolar::findOrFail($codigo_ano_escolar);
        $institucion = $this->cargarInstitucion(['director']);

        // Materias del plan de estudios de la sección
        $materias = PlanEstudios::with('materia')
            ->where('codigo_grado', $seccion->codigo_grado)
            ->where('id_mencion', $seccion->id_mencion)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->get()
            ->map(fn($pe) => (object)[
                'siglas'          => $pe->siglas_materia,
                'nombre'          => $pe->materia->nombre ?? $pe->siglas_materia,
                'tipo_evaluacion' => $pe->tipo_evaluacion,
            ]);

        // Estudiantes activos en la sección
        $matriculas = Matricula::activa()
            ->where('codigo_seccion', $codigo_seccion)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->with('estudiante')
            ->orderBy('numero_lista')
            ->get();

        // Evaluaciones de todos los estudiantes en un solo query
        $cedulas = $matriculas->pluck('cedula_estudiante');
        $todasEvaluaciones = Evaluacion::whereIn('cedula_estudiante', $cedulas)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->get()
            ->groupBy('cedula_estudiante');

        // Construir datos por estudiante
        $estudiantesData = $matriculas->map(function ($m) use ($todasEvaluaciones) {
            $evsEst = $todasEvaluaciones->get($m->cedula_estudiante, collect())
                ->groupBy('siglas_materia');

            return [
                'numero_lista' => $m->numero_lista,
                'cedula'       => $m->cedula_estudiante,
                'tipo_doc'     => $m->estudiante->tipo_documento ?? 'V',
                'nombres'      => $m->estudiante->nombres ?? '',
                'apellidos'    => $m->estudiante->apellidos ?? '',
                'evaluaciones' => $evsEst,
            ];
        })->values()->toArray();

        // Vista de conteo
        $conteoVista = VwSeccionConteo::find($codigo_seccion);

        $notaMinima = ParametroSistema::notaMinima();

        $data = [
            'institucion'     => $institucion,
            'seccion'         => $seccion,
            'anio'            => $anio,
            'materias'        => $materias,
            'estudiantesData' => $estudiantesData,
            'nota_minima'     => $notaMinima,
            'numero_momento'  => $numero_momento,
            'conteoVista'     => $conteoVista,
            'fecha_hoy'       => now()->format('d/m/Y'),
        ];

        return Pdf::loadView('pdf.resumen_seccion', $data)
            ->setPaper('letter', 'landscape')
            ->download("resumen_{$codigo_seccion}_{$codigo_ano_escolar}.pdf");
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
