<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InscripcionController extends Controller
{
    public function index()
    {
        // Excluir inscripciones cuya materia esté inactiva
        $periodo = request()->query('periodo');

        $query = Inscripcion::with(['estudiante', 'materia'])
            ->whereHas('materia', function($q) {
                $q->where('estado', 'activa');
            })
            ->latest();

        if (!empty($periodo)) {
            $query->where('periodo', $periodo);
        }

        $inscripciones = $query->get();

        return response()->json([
            'success' => true,
            'data' => $inscripciones
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'estudiante_id' => 'required|exists:estudiantes,id',
            'materia_id' => 'required|exists:materias,id',
            'periodo' => 'required|string',
            'fecha_inscripcion' => 'required|date',
            'estado' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // VALIDAR QUE LA MATERIA ESTÉ ACTIVA
        $materia = Materia::find($request->materia_id);
        if ($materia && strtolower($materia->estado) !== 'activa') {
            return response()->json([
                'success' => false,
                'error' => 'No se puede inscribir en una materia inactiva.'
            ], 422);
        }

        // VALIDAR INSCRIPCIÓN DUPLICADA
        $existe = Inscripcion::where('estudiante_id', $request->estudiante_id)
                ->where('materia_id', $request->materia_id)
                ->where('periodo', $request->periodo)
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'error' => "Este estudiante ya está inscrito en esta materia en el período {$request->periodo}."
                ], 422);
            }

            // CAPTURAR GRADO Y SECCIÓN DEL ESTUDIANTE EN ESTE MOMENTO
            // Congelamos el grado y sección en la inscripción
            $estudiante = Estudiante::find($request->estudiante_id);

            // VALIDAR QUE EL ESTUDIANTE ESTÉ ACTIVO
            if ($estudiante && !str_starts_with(strtolower((string)($estudiante->estado ?? '')), 'act')) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se puede inscribir a un estudiante inactivo.'
                ], 422);
            }
            
            // Preparar los datos para crear la inscripción
            $datosInscripcion = $request->all();
            $datosInscripcion['grado'] = $estudiante->grado;      // CAPTURA EL GRADO ACTUAL
            $datosInscripcion['seccion'] = $estudiante->seccion;  // CAPTURA LA SECCIÓN ACTUAL

            $inscripcion = Inscripcion::create($datosInscripcion);
            $inscripcion->load(['estudiante', 'materia']);

            return response()->json([
                'success' => true,
                'message' => 'Inscripción creada con éxito',
                'data' => $inscripcion
            ], 201);
        }

    public function show($id)
    {
        $inscripcion = Inscripcion::with(['estudiante', 'materia'])->find($id);

        if (!$inscripcion) {
            return response()->json([
                'success' => false,
                'message' => 'Inscripción no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $inscripcion
        ]);
    }

    public function update(Request $request, $id)
    {
        $inscripcion = Inscripcion::find($id);

        if (!$inscripcion) {
            return response()->json([
                'success' => false,
                'message' => 'Inscripción no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'estado' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $inscripcion->update($request->all());
        $inscripcion->load(['estudiante', 'materia']);

        return response()->json([
            'success' => true,
            'message' => 'Inscripción actualizada con éxito',
            'data' => $inscripcion
        ]);
    }

    public function destroy($id)
    {
        $inscripcion = Inscripcion::find($id);

        if (!$inscripcion) {
            return response()->json([
                'success' => false,
                'message' => 'Inscripción no encontrada'
            ], 404);
        }

        $inscripcion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inscripción eliminada con éxito'
        ]);
    }
}