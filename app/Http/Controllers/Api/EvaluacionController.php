<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class EvaluacionController extends Controller
{
    public function index()
    {
        $grado = request()->query('grado');
        $seccion = request()->query('seccion');
        $estado = request()->query('estado');
        $periodo = request()->query('periodo');

        $query = Evaluacion::with(['inscripcion.estudiante', 'inscripcion.materia'])->latest();

        if (!empty($grado)) {
            $query->whereHas('inscripcion', function($q) use ($grado) {
                $q->where('grado', $grado);
            });
        }

        if (!empty($seccion)) {
            $query->whereHas('inscripcion', function($q) use ($seccion) {
                $q->where('seccion', $seccion);
            });
        }

        if (!empty($periodo)) {
            $query->whereHas('inscripcion', function($q) use ($periodo) {
                $q->where('periodo', $periodo);
            });
        }

        if (!empty($estado)) {
            $query->where('estado', $estado);
        }

        $evaluaciones = $query->get();

        return response()->json([
            'success' => true,
            'data' => $evaluaciones
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'inscripcion_id' => 'required|exists:inscripciones,id|unique:evaluaciones',
            'nota_parcial1' => 'nullable|numeric|min:0|max:20',
            'nota_parcial2' => 'nullable|numeric|min:0|max:20',
            'nota_final' => 'nullable|numeric|min:0|max:20',
            'fecha' => 'required|date',
            'estado' => 'required|string',
            'promedio' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // VALIDAR QUE LA INSCRIPCIÓN ESTÉ ACTIVA
        $inscripcion = Inscripcion::find($request->inscripcion_id);
        if ($inscripcion && strtolower($inscripcion->estado) !== 'activa') {
            return response()->json([
                'success' => false,
                'error' => 'No se puede registrar evaluación para una inscripción inactiva.'
            ], 422);
        }

        $evaluacion = Evaluacion::create($request->all());
        $evaluacion->load(['inscripcion.estudiante', 'inscripcion.materia']);

        return response()->json([
            'success' => true,
            'message' => 'Evaluación creada con éxito',
            'data' => $evaluacion
        ], 201);
    }

    public function show($id)
    {
        $evaluacion = Evaluacion::with(['inscripcion.estudiante', 'inscripcion.materia'])->find($id);

        if (!$evaluacion) {
            return response()->json([
                'success' => false,
                'message' => 'Evaluación no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $evaluacion
        ]);
    }

    public function update(Request $request, $id)
    {
        // Usar find() en lugar de findOrFail para personalizar la respuesta
        $evaluacion = Evaluacion::find($id);

        if (!$evaluacion) {
            return response()->json([
                'success' => false,
                'message' => "Error: No existe la evaluación con ID: $id"
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nota_parcial1' => 'nullable|numeric|min:0|max:20',
            'nota_parcial2' => 'nullable|numeric|min:0|max:20',
            'nota_final' => 'nullable|numeric|min:0|max:20',
            'promedio' => 'required|numeric',
            'estado' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $evaluacion->fill($request->all());
        $evaluacion->estado = strtolower(trim($request->estado));
        $evaluacion->save();

        return response()->json([
            'success' => true,
            'message' => 'Evaluación actualizada con éxito',
            'data' => $evaluacion->load(['inscripcion.estudiante', 'inscripcion.materia'])
        ]);
    }

    public function destroy($id)
    {
        $evaluacion = Evaluacion::find($id);

        if (!$evaluacion) {
            return response()->json([
                'success' => false,
                'message' => 'Evaluación no encontrada'
            ], 404);
        }

        $evaluacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Evaluación eliminada con éxito'
        ]);
    }

    /**
     * REPORTE PDF
     */
    public function reportePDF(Request $request)
    {
        $grado = $request->query('grado');
        $seccion = $request->query('seccion');
        $estado = $request->query('estado');
        $periodo = $request->query('periodo');

        $query = Evaluacion::with(['inscripcion.estudiante', 'inscripcion.materia']);

        // Filtrar por grado/sección/periodo tomando los valores congelados en la inscripción
        if (!empty($grado)) {
            $query->whereHas('inscripcion', function($q) use ($grado) {
                $q->where('grado', $grado);
            });
        }

        if (!empty($seccion)) {
            $query->whereHas('inscripcion', function($q) use ($seccion) {
                $q->where('seccion', $seccion);
            });
        }

        if (!empty($periodo)) {
            $query->whereHas('inscripcion', function($q) use ($periodo) {
                $q->where('periodo', $periodo);
            });
        }

        if (!empty($estado)) {
            $query->where('estado', $estado);
        }

        $evaluaciones = $query->get();

        $data = [
            'titulo' => 'Reporte de Calificaciones',
            'fecha' => date('d/m/Y'),
            'evaluaciones' => $evaluaciones,
            'filtros' => [
                'grado' => $grado ?? 'Todos',
                'seccion' => $seccion ?? 'Todas',
                'periodo' => $periodo ?? 'Todos',
                'estado' => $estado ?? 'Todos'
            ]
        ];

        $pdf = Pdf::loadView('pdf.reporte_evaluaciones', $data);
        return $pdf->setPaper('a4', 'landscape')->stream("Reporte_Evaluaciones.pdf");
    }

    /**
     * HISTORIAL ACADÉMICO POR ESTUDIANTE
     */
    public function reporteAcademico($estudianteId)
    {
        $inscripciones = Inscripcion::with(['materia', 'evaluacion'])
            ->where('estudiante_id', $estudianteId)
            ->where('estado', 'activa') // INSCRIPCIONES ACTIVAS
            ->get();

        $materiasConCalificacion = $inscripciones->filter(fn($i) => $i->evaluacion !== null);
        $promedioGeneral = $materiasConCalificacion->avg(fn($i) => $i->evaluacion->promedio);

        $reporte = [
            'estudiante_id' => $estudianteId,
            'total_materias' => $inscripciones->count(),
            'promedio_general' => round($promedioGeneral, 2),
            'detalle' => $materiasConCalificacion->map(fn($i) => [
                'materia' => $i->materia->nombre,
                'promedio' => $i->evaluacion->promedio,
                'estado' => $i->evaluacion->estado,
            ])
        ];

        return response()->json(['success' => true, 'data' => $reporte]);
    }

    /**
     * Útil para cargar el selector de inscripciones en el frontend
     */
    public function inscripcionesPorEstudiante($estudianteId)
    {
        $inscripciones = Inscripcion::with('materia')
            ->where('estudiante_id', $estudianteId)
            ->where('estado', 'activa') // SOLO ACTIVAS
            ->get();

        return response()->json([
            'success' => true,
            'data' => $inscripciones
        ]);
    }
}