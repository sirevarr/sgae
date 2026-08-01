<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use App\Models\Matricula;
use App\Models\MateriaPendiente;
use App\Models\MomentoEvaluativo;
use App\Models\ParametroSistema;
use App\Models\PlanEstudios;
use App\Support\RoleAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EvaluacionController extends Controller
{
    /**
     * Obtener evaluaciones — filtrar por sección, año escolar y momento.
     * Devuelve una matriz pivotada por estudiante con sus notas.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'codigo_seccion'     => 'required|string',
            'codigo_ano_escolar' => 'required|string',
            'numero_momento'     => 'nullable|integer|in:1,2,3',
        ]);

        // Obtener estudiantes matriculados en la sección
        $matriculas = Matricula::activa()
            ->where('codigo_seccion', $request->codigo_seccion)
            ->where('codigo_ano_escolar', $request->codigo_ano_escolar)
            ->with('estudiante')
            ->orderBy('numero_lista')
            ->get();

        // Obtener plan de estudios de la sección
        $seccionModel = \App\Models\Seccion::findOrFail($request->codigo_seccion);

        $plan = PlanEstudios::with('materia')
            ->where('codigo_grado', $seccionModel->codigo_grado)
            ->where('id_mencion', $seccionModel->id_mencion)
            ->where('codigo_ano_escolar', $request->codigo_ano_escolar)
            ->get();

        // Obtener evaluaciones existentes
        $evalQuery = Evaluacion::where('codigo_ano_escolar', $request->codigo_ano_escolar)
            ->whereIn('cedula_estudiante', $matriculas->pluck('cedula_estudiante'));

        if ($request->numero_momento) {
            $evalQuery->where('numero_momento', $request->numero_momento);
        }

        $evaluaciones = $evalQuery->get()->groupBy(['cedula_estudiante', 'siglas_materia']);

        $momentos = MomentoEvaluativo::where('codigo_ano_escolar', $request->codigo_ano_escolar)
            ->orderBy('numero_momento')
            ->get();

        return response()->json([
            'matriculas'   => $matriculas,
            'plan'         => $plan,
            'evaluaciones' => $evaluaciones,
            'momentos'     => $momentos,
            'nota_minima'  => ParametroSistema::notaMinima(),
        ]);
    }

    /**
     * Guardar / actualizar una nota individual.
     */
    public function guardar(Request $request): JsonResponse
    {
        if (!RoleAccess::canEvaluate(Auth::user())) {
            return response()->json(['message' => 'No tiene permisos para registrar o modificar evaluaciones.'], 403);
        }

        $data = $request->validate([
            'cedula_estudiante'       => 'required|string|exists:Estudiante,cedula_estudiante',
            'siglas_materia'          => 'required|string|exists:Materia,siglas',
            'id_mencion'              => 'required|integer',
            'codigo_grado'            => 'required|string',
            'codigo_ano_escolar'      => 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
            'numero_momento'          => 'required|integer|in:1,2,3',
            'nota'                    => 'required|numeric|min:0|max:20',
            'cedula_docente_evaluador' => 'nullable|integer|exists:Docente,cedula_personal',
            'es_revision'             => 'sometimes|boolean',
            'motivo_modificacion'     => 'nullable|string',
        ]);

        if (!MomentoEvaluativo::where('numero_momento', $data['numero_momento'])
            ->where('codigo_ano_escolar', $data['codigo_ano_escolar'])
            ->exists()) {
            return response()->json([
                'message' => 'Momento evaluativo no existe para el año escolar indicado.',
            ], 422);
        }

        $evaluacion = Evaluacion::updateOrCreate(
            [
                'cedula_estudiante'  => $data['cedula_estudiante'],
                'siglas_materia'     => $data['siglas_materia'],
                'id_mencion'         => $data['id_mencion'],
                'codigo_grado'       => $data['codigo_grado'],
                'codigo_ano_escolar' => $data['codigo_ano_escolar'],
                'numero_momento'     => $data['numero_momento'],
            ],
            [
                'nota'                     => $data['nota'],
                'fecha_evaluacion'         => now()->toDateString(),
                'cedula_docente_evaluador' => $data['cedula_docente_evaluador'] ?? null,
                'es_revision'              => $data['es_revision'] ?? false,
                'fecha_modificacion'       => now()->toDateString(),
                'motivo_modificacion'      => $data['motivo_modificacion'] ?? null,
            ]
        );

        return response()->json([
            'evaluacion' => $evaluacion,
            'resultado'  => $evaluacion->resultado,
        ]);
    }

    /**
     * Guardar evaluaciones en lote para toda una sección / momento.
     * Body: { notas: [ {cedula_estudiante, siglas_materia, nota, ...}, ... ] }
     */
    public function guardarLote(Request $request): JsonResponse
    {
        if (!RoleAccess::canEvaluate(Auth::user())) {
            return response()->json(['message' => 'No tiene permisos para guardar evaluaciones en lote.'], 403);
        }

        $request->validate([
            'notas'                  => 'required|array|min:1',
            'notas.*.cedula_estudiante' => 'required|string',
            'notas.*.siglas_materia'    => 'required|string',
            'notas.*.id_mencion'        => 'required|integer',
            'notas.*.codigo_grado'      => 'required|string',
            'notas.*.codigo_ano_escolar' => 'required|string',
            'notas.*.numero_momento'    => 'required|integer|in:1,2,3',
            'notas.*.nota'              => 'required|numeric|min:0|max:20',
        ]);

        $momentosSolicitados = collect($request->notas)
            ->map(fn ($item) => [
                'numero_momento'     => $item['numero_momento'],
                'codigo_ano_escolar' => $item['codigo_ano_escolar'],
            ])
            ->unique()
            ->values();

        foreach ($momentosSolicitados as $momento) {
            if (!MomentoEvaluativo::where($momento)->exists()) {
                return response()->json([
                    'message' => "El momento {$momento['numero_momento']} no existe para el año escolar {$momento['codigo_ano_escolar']}.",
                ], 422);
            }
        }

        DB::transaction(function () use ($request) {
            foreach ($request->notas as $item) {
                Evaluacion::updateOrCreate(
                    [
                        'cedula_estudiante'  => $item['cedula_estudiante'],
                        'siglas_materia'     => $item['siglas_materia'],
                        'id_mencion'         => $item['id_mencion'],
                        'codigo_grado'       => $item['codigo_grado'],
                        'codigo_ano_escolar' => $item['codigo_ano_escolar'],
                        'numero_momento'     => $item['numero_momento'],
                    ],
                    [
                        'nota'             => $item['nota'],
                        'fecha_evaluacion' => now()->toDateString(),
                    ]
                );
            }
        });

        return response()->json(['message' => count($request->notas) . ' notas guardadas.']);
    }

    /**
     * Resumen de rendimiento por sección al finalizar todos los momentos.
     * Calcula promedios finales y determina aprobados/reprobados.
     */
    public function resumenSeccion(Request $request): JsonResponse
    {
        $request->validate([
            'codigo_seccion'     => 'required|string',
            'codigo_ano_escolar' => 'required|string',
        ]);

        $seccionModel = \App\Models\Seccion::with(['grado', 'mencion'])->findOrFail($request->codigo_seccion);

        $matriculas = Matricula::activa()
            ->where('codigo_seccion', $request->codigo_seccion)
            ->where('codigo_ano_escolar', $request->codigo_ano_escolar)
            ->with('estudiante')
            ->orderBy('numero_lista')
            ->get();

        $plan = PlanEstudios::with('materia')
            ->where('codigo_grado', $seccionModel->codigo_grado)
            ->where('id_mencion', $seccionModel->id_mencion)
            ->where('codigo_ano_escolar', $request->codigo_ano_escolar)
            ->get();

        $evaluaciones = Evaluacion::where('codigo_ano_escolar', $request->codigo_ano_escolar)
            ->whereIn('cedula_estudiante', $matriculas->pluck('cedula_estudiante'))
            ->get()
            ->groupBy(['cedula_estudiante', 'siglas_materia']);

        $notaMinima = ParametroSistema::notaMinima();
        $resumen    = [];

        foreach ($matriculas as $matricula) {
            $cedula    = $matricula->cedula_estudiante;
            $filaEst   = [];
            $promedios = [];

            // Acceso seguro via Collection::get() — evita TypeError si no hay notas para ese estudiante
            $evalsEstudiante = $evaluaciones->get($cedula);

            foreach ($plan as $pe) {
                $siglas       = $pe->siglas_materia;
                $evalsMateria = $evalsEstudiante?->get($siglas);
                $notas        = $this->obtenerNotasPorMomento($evalsMateria);
                $notaFinal    = $this->calcularNotaFinal($notas);
                $resultado    = $this->determinarResultado($notaFinal, $notaMinima);

                $filaEst[$siglas] = [
                    'm1'        => $notas[1],
                    'm2'        => $notas[2],
                    'm3'        => $notas[3],
                    'final'     => $notaFinal,
                    'resultado' => $resultado,
                ];

                if ($notaFinal !== null) {
                    $promedios[] = $notaFinal;
                }
            }

            $promedioGral = count($promedios) ? round(array_sum($promedios) / count($promedios), 2) : null;

            $resumen[] = [
                'estudiante'    => $matricula->estudiante,
                'numero_lista'  => $matricula->numero_lista,
                'materias'      => $filaEst,
                'promedio_gral' => $promedioGral,
            ];
        }

        return response()->json([
            'seccion'    => $seccionModel,
            'plan'       => $plan,
            'resumen'    => $resumen,
            'nota_minima' => $notaMinima,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        if (!RoleAccess::canManageRecords(Auth::user())) {
            return response()->json(['message' => 'Solo los usuarios con permisos de administración o control de estudios pueden eliminar evaluaciones.'], 403);
        }

        $data = $request->validate([
            'cedula_estudiante'  => 'required|string|exists:Estudiante,cedula_estudiante',
            'siglas_materia'     => 'required|string|exists:Materia,siglas',
            'id_mencion'         => 'required|integer',
            'codigo_grado'       => 'required|string',
            'codigo_ano_escolar' => 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
            'numero_momento'     => 'required|integer|in:1,2,3',
        ]);

        $deleted = Evaluacion::where($data)->delete();

        if ($deleted) {
            return response()->json(null, 204);
        }

        return response()->json(['message' => 'Evaluación no encontrada o no eliminada.'], 404);
    }

    private function obtenerNotasPorMomento(?iterable $evaluacionesMateria): array
    {
        $notas = [1 => null, 2 => null, 3 => null];
        if (! $evaluacionesMateria) {
            return $notas;
        }

        foreach ($evaluacionesMateria as $eval) {
            $momento = (int) $eval->numero_momento;
            if (isset($notas[$momento])) {
                $notas[$momento] = $eval->nota !== null ? (float) $eval->nota : null;
            }
        }

        return $notas;
    }

    private function calcularNotaFinal(array $notas): ?float
    {
        $validas = array_filter($notas, fn ($n) => $n !== null);
        if (empty($validas)) {
            return null;
        }

        return round(array_sum($validas) / count($validas), 2);
    }

    private function determinarResultado(?float $notaFinal, float $notaMinima): string
    {
        if ($notaFinal === null) {
            return 'P';
        }

        return $notaFinal >= $notaMinima ? 'A' : 'R';
    }
}