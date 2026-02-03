<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Estudiante;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InscripcionController extends Controller
{
    public function index()
    {
        // Cargamos las relaciones para ver nombres de alumnos y materias en la tabla
        $inscripciones = Inscripcion::with(['estudiante', 'materia'])->get();
        return response()->json(['success' => true, 'data' => $inscripciones]);
    }

    public function store(Request $request)
    {
        // 1. Validación básica de campos
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'materia_id'    => 'required|exists:materias,id',
            'periodo'       => 'required|string',
            'seccion'       => 'required|string',
        ]);

        $estudianteId = $request->estudiante_id;
        $periodo = $request->periodo;

        // --- VALIDACIÓN 1: Choque de Horarios (Mismo Periodo y Sección) ---
        // Verificamos si el alumno ya tiene una materia en esa sección durante ese periodo
        $choque = \App\Models\Inscripcion::where('estudiante_id', $estudianteId)
            ->where('periodo', $periodo)
            ->where('seccion', $request->seccion)
            ->first();

        if ($choque) {
            return response()->json([
                'error' => "El estudiante ya tiene la materia '{$choque->materia->nombre}' inscrita en la sección {$request->seccion} para este periodo."
            ], 422);
        }

        // --- VALIDACIÓN 2: Límite de Créditos (Máximo 25) ---
        // Sumamos los créditos de las materias ya inscritas en este periodo
        $creditosActuales = \App\Models\Inscripcion::where('estudiante_id', $estudianteId)
            ->where('periodo', $periodo)
            ->with('materia')
            ->get()
            ->sum(function ($inscripcion) {
                return $inscripcion->materia->creditos;
            });

        $nuevaMateria = \App\Models\Materia::find($request->materia_id);
        
        if (($creditosActuales + $nuevaMateria->creditos) > 25) {
            return response()->json([
                'error' => "Límite de créditos excedido. El alumno ya tiene {$creditosActuales} créditos y esta materia suma {$nuevaMateria->creditos} más (Máximo permitido: 25)."
            ], 422);
        }

        // --- SI PASA LAS VALIDACIONES, SE GUARDA ---
        $inscripcion = \App\Models\Inscripcion::create($request->all());
        
        return response()->json([
            'message' => 'Inscripción realizada con éxito',
            'data' => $inscripcion->load(['estudiante', 'materia'])
        ], 201);
    }

    public function getFormData()
    {
        try {
            // Usamos all() primero para ver si funciona, luego filtramos
            $estudiantes = \App\Models\Estudiante::where('estado', 'activo')->get();
            $materias = \App\Models\Materia::where('estado', 'activa')->get();
            
            return response()->json([
                'estudiantes' => $estudiantes,
                'materias' => $materias
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $inscripcion = Inscripcion::findOrFail($id);
        $inscripcion->update($request->all());
        return response()->json($inscripcion);
    }
}