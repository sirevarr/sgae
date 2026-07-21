<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MateriaPendiente;
use App\Models\Estudiante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD completo para Materia_Pendiente.
 * Registra materias aplazadas de un estudiante de años anteriores.
 */
class MateriaPendienteController extends Controller
{
    /** GET /api/materias-pendientes?cedula_estudiante=... */
    public function index(Request $request): JsonResponse
    {
        $q = MateriaPendiente::with(['estudiante', 'materia', 'grado', 'anioEscolarOrigen'])
            ->when($request->filled('cedula_estudiante'), fn ($query) =>
                $query->where('cedula_estudiante', $request->cedula_estudiante)
            )
            ->when($request->filled('estado'), fn ($query) =>
                $query->where('estado', $request->estado)
            )
            ->get();

        return response()->json(['success' => true, 'data' => $q]);
    }

    /** GET /api/materias-pendientes/{id} */
    public function show(int $id): JsonResponse
    {
        $mp = MateriaPendiente::with(['estudiante', 'materia', 'grado', 'anioEscolarOrigen'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $mp]);
    }

    /** POST /api/materias-pendientes */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cedula_estudiante'        => 'required|string|exists:Estudiante,cedula_estudiante',
            'siglas_materia'           => 'required|string|exists:Materia,siglas',
            'id_mencion'               => 'nullable|integer|exists:Mencion,id_mencion',
            'codigo_grado'             => 'required|string|exists:Grado,codigo_grado',
            'codigo_ano_escolar_origen'=> 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
            'estado'                   => 'required|string|in:pendiente,aprobada,no_presentada',
            'fecha_resolucion'         => 'nullable|date',
            'nota_final'               => 'nullable|numeric|min:0|max:20',
        ]);

        $mp = MateriaPendiente::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Materia pendiente registrada.',
            'data'    => $mp->load(['materia', 'grado']),
        ], 201);
    }

    /** PUT /api/materias-pendientes/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $mp = MateriaPendiente::findOrFail($id);

        $data = $request->validate([
            'estado'           => 'sometimes|string|in:pendiente,aprobada,no_presentada',
            'fecha_resolucion' => 'sometimes|nullable|date',
            'nota_final'       => 'sometimes|nullable|numeric|min:0|max:20',
        ]);

        $mp->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Materia pendiente actualizada.',
            'data'    => $mp->fresh(['materia', 'grado']),
        ]);
    }

    /** DELETE /api/materias-pendientes/{id} */
    public function destroy(int $id): JsonResponse
    {
        $mp = MateriaPendiente::findOrFail($id);
        $mp->delete();

        return response()->json(['success' => true, 'message' => 'Materia pendiente eliminada.']);
    }
}
