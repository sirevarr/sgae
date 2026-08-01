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
use Carbon\Carbon;

/**
 * Servicio de generación de documentos PDF para SGAE utilizando ReportLab (Python).
 * Garantiza 100% de paridad estética y de diseño con los formatos de Gesman.
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

    private function formatInstitucionData(?Institucion $inst): array
    {
        $dir = $inst?->director;
        $coord = $inst?->coordinador;

        return [
            'nombre'             => $inst?->nombre ?? 'Unidad Educativa Estadal “Carmen Ruiz”',
            'codigo_plantel'     => $inst?->codigo_plantel ?? 'OD00221508',
            'ciudad'             => $inst?->ciudad ?? $inst?->municipio ?? 'Charallave – Cristóbal Rojas',
            'estado'             => $inst?->estado ?? 'Estado Bolivariano de Miranda',
            'telefono'           => $inst?->telefono ?? '0239-2487847',
            'director_nombre'    => $dir ? "{$dir->nombres} {$dir->apellidos}" : 'DIRECTOR (A)',
            'coordinador_nombre' => $coord ? "{$coord->nombres} {$coord->apellidos}" : 'COORDINADOR (A) PEDAGÓGICO',
        ];
    }

    private function formatEstudianteData(Estudiante $est): array
    {
        $fnac = $est->fecha_nacimiento ? Carbon::parse($est->fecha_nacimiento) : null;

        return [
            'cedula'           => $est->cedula_estudiante,
            'tipo_documento'   => $est->tipo_documento ?? 'V',
            'nombres'          => $est->nombres,
            'apellidos'        => $est->apellidos,
            'fecha_nacimiento' => $fnac ? $fnac->format('d/m/Y') : '',
            'edad'             => $fnac ? $fnac->age : '___',
        ];
    }

    private function formatSeccionData(?Seccion $seccion): array
    {
        if (!$seccion) return [];

        $docente = $seccion->docenteGuia?->personal;
        $docenteNombre = $docente ? "{$docente->nombres} {$docente->apellidos}" : 'Sin asignación';

        $gradoNombre = $seccion->grado?->nombre ?? 'Grado/Año';
        $gradoNum = (int) preg_replace('/[^0-9]/', '', $gradoNombre);

        return [
            'codigo'       => $seccion->codigo_seccion,
            'letra'        => $seccion->letra,
            'nombre_grado' => $gradoNombre,
            'numero_grado' => $gradoNum > 0 ? $gradoNum : 1,
            'docente_guia' => $docenteNombre,
            'mencion'      => [
                'nombre' => $seccion->mencion?->nombre ?? 'Educación General',
            ]
        ];
    }

    /**
     * Ejecuta el script de Python con ReportLab para generar el PDF.
     */
    private function generarReportLabPdf(string $tipo, array $data, string $downloadFilename): \Illuminate\Http\Response
    {
        $pythonExe = 'C:\\Users\\sires\\.cache\\codex-runtimes\\codex-primary-runtime\\dependencies\\python\\python.exe';
        $scriptPath = base_path('app/Scripts/generar_pdf.py');

        $tempJson = tempnam(sys_get_temp_dir(), 'sgae_pdf_data_') . '.json';
        $tempPdf  = tempnam(sys_get_temp_dir(), 'sgae_pdf_out_') . '.pdf';

        file_put_contents($tempJson, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $cmd = sprintf(
            '"%s" "%s" --tipo "%s" --json "%s" --output "%s" 2>&1',
            $pythonExe,
            $scriptPath,
            $tipo,
            $tempJson,
            $tempPdf
        );

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($tempPdf)) {
            @unlink($tempJson);
            if (file_exists($tempPdf)) {
                @unlink($tempPdf);
            }
            $errorMsg = implode("\n", $output);
            abort(500, "Error al generar PDF con ReportLab: " . $errorMsg);
        }

        $pdfContent = file_get_contents($tempPdf);

        @unlink($tempJson);
        @unlink($tempPdf);

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $downloadFilename . '"',
        ]);
    }

    // ─────────────────────────────────────────────
    //  BOLETÍN DE CALIFICACIONES
    // ─────────────────────────────────────────────

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

        $evaluacionQuery = Evaluacion::where('cedula_estudiante', $cedula_estudiante)
            ->where('codigo_ano_escolar', $codigo_ano_escolar);

        if ($numero_momento) {
            $evaluacionQuery->where('numero_momento', '<=', $numero_momento);
        }

        $evaluaciones = $evaluacionQuery->get()
            ->groupBy('siglas_materia');

        $ficha = FichaAntropometrica::where('cedula_estudiante', $cedula_estudiante)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->first();

        $materiasData = $plan->map(function ($pe) use ($evaluaciones) {
            $evs = $evaluaciones->get($pe->siglas_materia, collect());
            $m1 = $evs->where('numero_momento', 1)->first()?->nota ?? '-';
            $m2 = $evs->where('numero_momento', 2)->first()?->nota ?? '-';
            $m3 = $evs->where('numero_momento', 3)->first()?->nota ?? '-';

            $notas = array_filter([$m1, $m2, $m3], fn($v) => is_numeric($v));
            $def = count($notas) > 0 ? round(array_sum($notas) / count($notas), 1) : '-';

            return [
                'siglas' => $pe->siglas_materia,
                'nombre' => $pe->materia->nombre ?? $pe->siglas_materia,
                'm1'     => $m1,
                'm2'     => $m2,
                'm3'     => $m3,
                'def'    => $def,
                'tipo_evaluacion' => $pe->tipo_evaluacion ?? 'N',
            ];
        })->values()->toArray();

        $payload = [
            'institucion'    => $this->formatInstitucionData($institucion),
            'estudiante'     => $this->formatEstudianteData($estudiante),
            'anio'           => ['codigo' => $anio->codigo_ano_escolar, 'descripcion' => $anio->codigo_ano_escolar],
            'seccion'        => $this->formatSeccionData($seccion),
            'tipo_boletin'   => $numero_momento ? "Momento {$numero_momento}" : 'Final',
            'numero_momento' => $numero_momento,
            'fecha_emision'  => now()->format('d/m/Y'),
            'materias'       => $materiasData,
            'ficha'          => $ficha ? [
                'peso' => $ficha->peso,
                'talla' => $ficha->estatura,
                'imc' => ($ficha->estatura && $ficha->peso) ? round($ficha->peso / (($ficha->estatura / 100) ** 2), 1) : null,
            ] : null,
        ];

        return $this->generarReportLabPdf('boletin', $payload, "boletin_{$cedula_estudiante}_{$codigo_ano_escolar}.pdf");
    }

    // ─────────────────────────────────────────────
    //  CONSTANCIA DE ESTUDIO
    // ─────────────────────────────────────────────

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

        $payload = [
            'institucion'   => $this->formatInstitucionData($institucion),
            'estudiante'    => $this->formatEstudianteData($estudiante),
            'anio'          => ['codigo' => $anio->codigo_ano_escolar, 'descripcion' => $anio->codigo_ano_escolar],
            'seccion'       => $this->formatSeccionData($matricula->seccion),
            'motivo'        => $motivo ?: 'los fines que el solicitante estime convenientes',
            'fecha_emision' => now()->format('d/m/Y'),
        ];

        return $this->generarReportLabPdf('constancia_estudio', $payload, "constancia_{$cedula_estudiante}.pdf");
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

        $payload = [
            'institucion'   => $this->formatInstitucionData($institucion),
            'estudiante'    => $this->formatEstudianteData($estudiante),
            'anio'          => ['codigo' => $anio->codigo_ano_escolar, 'descripcion' => $anio->codigo_ano_escolar],
            'seccion'       => $this->formatSeccionData($matricula->seccion),
            'fecha_emision' => now()->format('d/m/Y'),
        ];

        return $this->generarReportLabPdf('constancia_conducta', $payload, "buena_conducta_{$cedula_estudiante}.pdf");
    }

    // ─────────────────────────────────────────────
    //  CONSTANCIA DE PROSECUCIÓN
    // ─────────────────────────────────────────────

    public function constanciaProsecucion(string $cedula_estudiante, string $codigo_ano_escolar): \Illuminate\Http\Response
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

        $gradoNum = (int) preg_replace('/[^0-9]/', '', $matricula->seccion->grado->nombre ?? '1');
        $gradoPromovidoNum = $gradoNum + 1;

        $payload = [
            'institucion'     => $this->formatInstitucionData($institucion),
            'estudiante'      => $this->formatEstudianteData($estudiante),
            'anio'            => ['codigo' => $anio->codigo_ano_escolar, 'descripcion' => $anio->codigo_ano_escolar],
            'seccion'         => $this->formatSeccionData($matricula->seccion),
            'grado_promovido' => "{$gradoPromovidoNum}° Año / Grado",
            'fecha_emision'   => now()->format('d/m/Y'),
        ];

        return $this->generarReportLabPdf('constancia_prosecucion', $payload, "prosecucion_{$cedula_estudiante}.pdf");
    }

    // ─────────────────────────────────────────────
    //  CONSTANCIA DE ASISTENCIA
    // ─────────────────────────────────────────────

    public function constanciaAsistencia(string $cedula_estudiante, string $codigo_ano_escolar): \Illuminate\Http\Response
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

        $payload = [
            'institucion'   => $this->formatInstitucionData($institucion),
            'estudiante'    => $this->formatEstudianteData($estudiante),
            'anio'          => ['codigo' => $anio->codigo_ano_escolar, 'descripcion' => $anio->codigo_ano_escolar],
            'seccion'       => $this->formatSeccionData($matricula->seccion),
            'fecha_emision' => now()->format('d/m/Y'),
        ];

        return $this->generarReportLabPdf('constancia_asistencia', $payload, "asistencia_{$cedula_estudiante}.pdf");
    }

    // ─────────────────────────────────────────────
    //  LISTA DE SECCIÓN
    // ─────────────────────────────────────────────

    public function listaSeccion(string $codigo_seccion, string $codigo_ano_escolar): \Illuminate\Http\Response
    {
        $seccion     = Seccion::with(['grado', 'mencion', 'docenteGuia.personal'])->findOrFail($codigo_seccion);
        $anio        = AnioEscolar::findOrFail($codigo_ano_escolar);
        $institucion = $this->cargarInstitucion(['director', 'coordinador']);

        $matriculas = Matricula::activa()
            ->where('codigo_seccion', $codigo_seccion)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->with('estudiante')
            ->orderBy('numero_lista')
            ->get();

        $conteoVista = VwSeccionConteo::find($codigo_seccion);

        $matsData = $matriculas->map(function ($m, $idx) {
            $est = $m->estudiante;
            $fnac = $est?->fecha_nacimiento ? Carbon::parse($est->fecha_nacimiento) : null;
            $edad = $fnac ? $fnac->age : null;
            $raw_genero = $est?->genero ?? $est?->sexo ?? null;
            $sexo = $raw_genero ? strtoupper(substr((string)$raw_genero, 0, 1)) : 'M';
            return [
                'numero_lista'     => $m->numero_lista ?? ($idx + 1),
                'cedula'           => $m->cedula_estudiante,
                'tipo_documento'   => $est?->tipo_documento ?? 'V',
                'nombres'          => $est?->nombres ?? '',
                'apellidos'        => $est?->apellidos ?? '',
                'sexo'             => $sexo,
                'fecha_nacimiento' => $fnac ? $fnac->format('d/m/Y') : '',
                'edad'             => $edad,
            ];
        })->values()->toArray();

        $payload = [
            'institucion' => $this->formatInstitucionData($institucion),
            'seccion'     => $this->formatSeccionData($seccion),
            'anio'        => ['codigo' => $anio->codigo_ano_escolar, 'descripcion' => $anio->codigo_ano_escolar],
            'matriculas'  => $matsData,
            'conteo'      => [
                'varones' => $conteoVista?->estudiantes_varones ?? 0,
                'hembras' => $conteoVista?->estudiantes_hembras ?? 0,
                'total'   => $conteoVista?->total_estudiantes ?? count($matsData),
            ],
            'fecha_hoy'   => now()->format('d/m/Y'),
        ];

        return $this->generarReportLabPdf('lista_seccion', $payload, "lista_{$codigo_seccion}.pdf");
    }

    // ─────────────────────────────────────────────
    //  RESUMEN DE SECCIÓN (LIBRO DE CALIFICACIONES)
    // ─────────────────────────────────────────────

    public function resumenSeccion(string $codigo_seccion, string $codigo_ano_escolar, ?int $numero_momento = null): \Illuminate\Http\Response
    {
        $seccion     = Seccion::with(['grado', 'mencion', 'docenteGuia.personal'])->findOrFail($codigo_seccion);
        $anio        = AnioEscolar::findOrFail($codigo_ano_escolar);
        $institucion = $this->cargarInstitucion(['director', 'coordinador']);

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

        $matriculas = Matricula::activa()
            ->where('codigo_seccion', $codigo_seccion)
            ->where('codigo_ano_escolar', $codigo_ano_escolar)
            ->with('estudiante')
            ->orderBy('numero_lista')
            ->get();

        $cedulas = $matriculas->pluck('cedula_estudiante');
        $evaluacionQuery = Evaluacion::whereIn('cedula_estudiante', $cedulas)
            ->where('codigo_ano_escolar', $codigo_ano_escolar);

        if ($numero_momento) {
            $evaluacionQuery->where('numero_momento', '<=', $numero_momento);
        }

        $todasEvaluaciones = $evaluacionQuery->get()
            ->groupBy('cedula_estudiante');

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

        $conteoVista = VwSeccionConteo::find($codigo_seccion);

        $materiasData = $materias->map(fn($m) => [
            'siglas'           => $m->siglas,
            'nombre'           => $m->nombre,
            'tipo_evaluacion'  => $m->tipo_evaluacion,
        ])->values()->toArray();

        $estDataProcessed = array_map(function ($est) {
            $evsMapped = [];
            foreach ($est['evaluaciones'] as $siglas => $collection) {
                $evsMapped[$siglas] = [
                    1 => $collection->firstWhere('numero_momento', 1)?->nota ?? '-',
                    2 => $collection->firstWhere('numero_momento', 2)?->nota ?? '-',
                    3 => $collection->firstWhere('numero_momento', 3)?->nota ?? '-',
                ];
            }
            return [
                'numero_lista' => $est['numero_lista'],
                'cedula'       => $est['cedula'],
                'tipo_doc'     => $est['tipo_doc'],
                'nombres'      => $est['nombres'],
                'apellidos'    => $est['apellidos'],
                'evaluaciones' => $evsMapped,
            ];
        }, $estudiantesData);

        $payload = [
            'institucion'     => $this->formatInstitucionData($institucion),
            'seccion'         => $this->formatSeccionData($seccion),
            'anio'            => ['codigo' => $anio->codigo_ano_escolar, 'descripcion' => $anio->codigo_ano_escolar],
            'materias'        => $materiasData,
            'estudiantesData' => $estDataProcessed,
            'tipo_boletin'    => $numero_momento ? "Momento {$numero_momento}" : 'Final',
            'numero_momento'  => $numero_momento,
            'nota_minima'     => ParametroSistema::notaMinima(),
            'conteo'          => [
                'varones' => $conteoVista?->estudiantes_varones ?? 0,
                'hembras' => $conteoVista?->estudiantes_hembras ?? 0,
                'total'   => $conteoVista?->total_estudiantes ?? count($estDataProcessed),
            ],
            'fecha_hoy'       => now()->format('d/m/Y'),
        ];

        return $this->generarReportLabPdf('resumen_seccion', $payload, "resumen_{$codigo_seccion}_{$codigo_ano_escolar}.pdf");
    }

    /**
     * Genera un PDF y retorna su contenido binario (para guardar en Documento_Emitido).
     */
    public function generarBytes(string $tipo, array $data): string
    {
        $pythonExe = 'C:\\Users\\sires\\.cache\\codex-runtimes\\codex-primary-runtime\\dependencies\\python\\python.exe';
        $scriptPath = base_path('app/Scripts/generar_pdf.py');

        $tempJson = tempnam(sys_get_temp_dir(), 'sgae_pdf_data_') . '.json';
        $tempPdf  = tempnam(sys_get_temp_dir(), 'sgae_pdf_out_') . '.pdf';

        file_put_contents($tempJson, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $cmd = sprintf(
            '"%s" "%s" --tipo "%s" --json "%s" --output "%s" 2>&1',
            $pythonExe,
            $scriptPath,
            $tipo,
            $tempJson,
            $tempPdf
        );

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($tempPdf)) {
            @unlink($tempJson);
            if (file_exists($tempPdf)) {
                @unlink($tempPdf);
            }
            $errorMsg = implode("\n", $output);
            throw new \Exception("Error al generar PDF con ReportLab: " . $errorMsg);
        }

        $pdfContent = file_get_contents($tempPdf);

        @unlink($tempJson);
        @unlink($tempPdf);

        return $pdfContent;
    }

    // ─────────────────────────────────────────────
    //  RF-07: RESUMEN DE REVISIÓN (MATERIAS PENDIENTES)
    // ─────────────────────────────────────────────

    /**
     * Genera el PDF de "Resumen de Revisión" para un estudiante.
     * Lista las materias de Materia_Pendiente con su estado, nota final y fecha de resolución.
     *
     * El folio se diferencia del resumen ordinario mediante el prefijo "REV-"
     * asignado en DocumentoController::registrarEmisionRevision().
     */
    public function resumenRevision(string $cedula_estudiante, string $codigo_ano_escolar_origen): \Illuminate\Http\Response
    {
        $estudiante  = Estudiante::findOrFail($cedula_estudiante);
        $institucion = $this->cargarInstitucion(['director', 'coordinador']);

        // Consultar materias pendientes para ese estudiante y año escolar de origen
        $pendientes = MateriaPendiente::with(['materia', 'grado', 'anioEscolarOrigen'])
            ->where('cedula_estudiante', $cedula_estudiante)
            ->where('codigo_ano_escolar_origen', $codigo_ano_escolar_origen)
            ->orderBy('siglas_materia')
            ->get();

        // Construir payload para el script Python (mismo patrón que otros métodos)
        $pendientesData = $pendientes->map(function ($mp) {
            return [
                'siglas_materia'            => $mp->siglas_materia,
                'nombre_materia'            => $mp->materia->nombre ?? $mp->siglas_materia,
                'codigo_grado'              => $mp->codigo_grado,
                'nombre_grado'              => $mp->grado->nombre ?? $mp->codigo_grado,
                'codigo_ano_escolar_origen' => $mp->codigo_ano_escolar_origen,
                'estado'                    => $mp->estado ?? 'pendiente',
                'nota_final'                => $mp->nota_final,
                'fecha_resolucion'          => $mp->fecha_resolucion
                    ? \Carbon\Carbon::parse($mp->fecha_resolucion)->format('d/m/Y')
                    : null,
            ];
        })->values()->toArray();

        $payload = [
            'institucion'               => $this->formatInstitucionData($institucion),
            'estudiante'                => $this->formatEstudianteData($estudiante),
            'codigo_ano_escolar_origen' => $codigo_ano_escolar_origen,
            'pendientes'                => $pendientesData,
            'total_pendientes'          => count($pendientesData),
            'fecha_hoy'                 => now()->format('d/m/Y H:i'),
        ];

        // Intentar generar con ReportLab Python (mismo mecanismo que el resto de PDFs)
        try {
            return $this->generarReportLabPdf(
                'resumen_revision',
                $payload,
                "resumen_revision_{$cedula_estudiante}_{$codigo_ano_escolar_origen}.pdf"
            );
        } catch (\Throwable $e) {
            // Fallback: renderizar desde la vista Blade y devolver como HTML/PDF
            \Illuminate\Support\Facades\Log::warning(
                "[PdfService] resumenRevision fallback a Blade: " . $e->getMessage()
            );

            $html = view('pdf.resumen_revision', [
                'institucion'               => $institucion,
                'estudiante'                => $estudiante,
                'pendientes'                => $pendientes,
                'codigo_ano_escolar_origen' => $codigo_ano_escolar_origen,
                'fecha_hoy'                 => now()->format('d/m/Y H:i'),
            ])->render();

            return response($html, 200, [
                'Content-Type'        => 'text/html; charset=UTF-8',
                'Content-Disposition' => "inline; filename=\"resumen_revision_{$cedula_estudiante}.html\"",
            ]);
        }
    }
}
