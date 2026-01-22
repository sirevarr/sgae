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

        // Búsqueda avanzada
        if ($request->has('buscar')) {
            $query->buscar($request->buscar);
        }

        // Filtro por estado
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
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
            'cedula' => 'required|string|max:20',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'genero' => 'required|in:M,F',
            'fecha_nacimiento' => 'required|date',
            'email' => 'nullable|email|unique:estudiantes',
            'telefono' => 'nullable|string|max:20',
            // Los demás campos son opcionales
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Agregar estado por defecto si no viene
        $data = $request->all();
        if (!isset($data['estado'])) {
            $data['estado'] = 'activo';
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
        $estudiante = Estudiante::find($id);

        if (!$estudiante) {
            return response()->json([
                'success' => false,
                'message' => 'Estudiante no encontrado'
            ], 404);
        }

        $estudiante->delete();

        return response()->json([
            'success' => true,
            'message' => 'Estudiante eliminado exitosamente'
        ]);
    }
}