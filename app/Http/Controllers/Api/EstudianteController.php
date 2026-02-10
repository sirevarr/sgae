<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EstudianteController extends Controller
{
    // GET /api/estudiantes
    public function index(Request $request)
    {
        $query = Estudiante::query();

        // Búsqueda avanzada (Cédula, Nombre o Apellido)
        if ($request->has('buscar') && $request->buscar != '') {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('cedula', 'like', "%$buscar%")
                  ->orWhere('nombres', 'like', "%$buscar%")
                  ->orWhere('apellidos', 'like', "%$buscar%");
            });
        }

        // Filtro por estado
        if ($request->has('estado') && $request->estado != '') {
            $query->where('estado', $request->estado);
        }

        // Filtro por grado
        if ($request->has('grado') && $request->grado != '') {
            $query->where('grado', $request->grado);
        }

        $estudiantes = $query->orderBy('apellidos')->get();

        return response()->json([
            'success' => true,
            'data' => $estudiantes
        ]);
    }

    // POST /api/estudiantes
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cedula' => 'required|string|max:20|unique:estudiantes',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'genero' => 'required|in:M,F',
            'fecha_nacimiento' => 'required|date',
            'grado' => 'required|string',
            'seccion' => 'required|string',
            'email' => 'nullable|email|unique:estudiantes',
            'telefono' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        // Normalizamos el estado para que coincida con la vista
        if (!isset($data['estado'])) {
            $data['estado'] = 'Activo';
        }

        $estudiante = Estudiante::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Estudiante creado exitosamente',
            'data' => $estudiante
        ], 201);
    }

    // GET /api/estudiantes/{id}
    public function show($id)
    {
        // Traemos al estudiante con sus relaciones para ver su historial
        $estudiante = Estudiante::with(['inscripciones.materia', 'inscripciones.evaluacion'])
            ->find($id);

        if (!$estudiante) {
            return response()->json([
                'success' => false,
                'message' => 'Estudiante no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $estudiante
        ]);
    }

    // PUT /api/estudiantes/{id}
    public function update(Request $request, $id)
    {
        $estudiante = Estudiante::find($id);

        if (!$estudiante) {
            return response()->json([
                'success' => false,
                'message' => 'Estudiante no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'cedula' => 'required|string|max:20|unique:estudiantes,cedula,' . $id,
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'genero' => 'required|in:M,F',
            'grado' => 'required|string',
            'seccion' => 'required|string',
            'fecha_nacimiento' => 'required|date',
            'email' => 'nullable|email|unique:estudiantes,email,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $estudiante->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Estudiante actualizado exitosamente',
            'data' => $estudiante
        ]);
    }

    // DELETE /api/estudiantes/{id}
    public function destroy($id)
    {
        try {
            $estudiante = Estudiante::find($id);

            if (!$estudiante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estudiante no encontrado'
                ], 404);
            }

            // Intentamos eliminar
            $estudiante->delete();

            return response()->json([
                'success' => true,
                'message' => 'Estudiante eliminado exitosamente'
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            // Este error ocurre si el estudiante tiene notas o inscripciones (FK Constraint)
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: El estudiante tiene registros de inscripciones o notas vinculadas.'
            ], 422);
        }
    }
}