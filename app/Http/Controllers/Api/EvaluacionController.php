<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvaluacionController extends Controller
{
    public function index()
    {
        $evaluaciones = Evaluacion::with(['inscripcion.estudiante', 'inscripcion.materia'])->get();

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
            'fecha' => 'required|date', // <--- Validación de la fecha
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $evaluacion = Evaluacion::create($request->all());
        $evaluacion->load(['inscripcion.estudiante', 'inscripcion.materia']);

        return response()->json([
            'success' => true,
            'message' => 'Evaluación creada exitosamente',
            'data' => $evaluacion
        ], 201);
    }

    // FUNCIONALIDAD ESPECIAL: Reporte académico del estudiante
    public function reporteAcademico($estudianteId)
    {
        $inscripciones = Inscripcion::with(['materia', 'evaluacion'])
                                    ->where('estudiante_id', $estudianteId)
                                    ->get();

        $materiasConCalificacion = $inscripciones->filter(function ($inscripcion) {
            return $inscripcion->evaluacion !== null;
        });

        $promedioGeneral = $materiasConCalificacion->avg(function ($inscripcion) {
            return $inscripcion->evaluacion->promedio;
        });

        $reporte = [
            'estudiante_id' => $estudianteId,
            'total_materias_cursadas' => $inscripciones->count(),
            'materias_calificadas' => $materiasConCalificacion->count(),
            'promedio_general' => round($promedioGeneral, 2),
            'detalle' => $materiasConCalificacion->map(function ($inscripcion) {
                return [
                    'materia' => $inscripcion->materia->nombre,
                    'codigo' => $inscripcion->materia->codigo_materia,
                    'creditos' => $inscripcion->materia->creditos,
                    'periodo' => $inscripcion->periodo,
                    'parcial1' => $inscripcion->evaluacion->nota_parcial1,
                    'parcial2' => $inscripcion->evaluacion->nota_parcial2,
                    'final' => $inscripcion->evaluacion->nota_final,
                    'promedio' => $inscripcion->evaluacion->promedio,
                    'estado' => $inscripcion->evaluacion->estado,
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $reporte
        ]);
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
        $evaluacion = Evaluacion::find($id);

        if (!$evaluacion) {
            return response()->json([
                'success' => false,
                'message' => 'Evaluación no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nota_parcial1' => 'nullable|numeric|min:0|max:20',
            'nota_parcial2' => 'nullable|numeric|min:0|max:20',
            'nota_final' => 'nullable|numeric|min:0|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $evaluacion->update($request->all());
        $evaluacion->load(['inscripcion.estudiante', 'inscripcion.materia']);

        return response()->json([
            'success' => true,
            'message' => 'Evaluación actualizada exitosamente',
            'data' => $evaluacion
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
            'message' => 'Evaluación eliminada exitosamente'
        ]);
    }

    public function inscripcionesPorEstudiante($estudianteId)
    {
        // Esto es vital para que el selector de "Materia" en el modal funcione bien
        $inscripciones = Inscripcion::with('materia')
            ->where('estudiante_id', $estudianteId)
            ->where('estado', 'activa')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $inscripciones
        ]);
    }
}