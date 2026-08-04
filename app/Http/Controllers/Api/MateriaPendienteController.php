<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MateriaPendiente;
use App\Models\Estudiante;
use App\Models\Traits\Auditable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD completo para Materia_Pendiente.
 * Registra materias aplazadas de un estudiante de años anteriores.
 */
class MateriaPendienteController extends Controller
{
    use Auditable;

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
            'estado'                   => 'required|string|in:pendiente,aprobada,no_aprobada',
            'fecha_resolucion'         => 'nullable|date',
            'nota_final'               => 'nullable|numeric|min:0|max:20',
        ]);

        $anioActivo = \App\Models\AnioEscolar::vigente();
        if ($anioActivo && $data['codigo_ano_escolar_origen'] === $anioActivo->codigo_ano_escolar) {
            return response()->json([
                'message' => 'No puedes registrar una materia pendiente para el año escolar actual en curso. Las recuperaciones de este año se manejan en la grilla principal marcándolas en Revisión.'
            ], 422);
        }

        $existente = MateriaPendiente::where('cedula_estudiante', $data['cedula_estudiante'])
            ->where('siglas_materia', $data['siglas_materia'])
            ->where('codigo_ano_escolar_origen', $data['codigo_ano_escolar_origen'])
            ->first();

        if ($existente) {
            return response()->json([
                'message' => 'Esta materia ya está registrada como pendiente para este año escolar origen.'
            ], 422);
        }

        try {
            $mp = MateriaPendiente::create($data);

            // Auditoría: INSERT
            self::registrarAuditoria(
                'Materia_Pendiente',
                (string) $mp->id_materia_pendiente,
                'I',
                null,
                $mp->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Materia pendiente registrada.',
                'data'    => $mp->load(['materia', 'grado']),
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'FK_Materia_Pendient_3B16B004') || str_contains($e->getMessage(), 'Plan_Estudios')) {
                return response()->json([
                    'message' => "Error: La materia '{$data['siglas_materia']}' no está registrada en el Plan de Estudios para el grado {$data['codigo_grado']} en el año escolar {$data['codigo_ano_escolar_origen']} con esa mención. Verifica el Grado, la Mención y el Año Origen."
                ], 422);
            }
            throw $e;
        }
    }

    /** PUT /api/materias-pendientes/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $mp = MateriaPendiente::findOrFail($id);

        $data = $request->validate([
            'estado'                   => 'required|string|in:pendiente,aprobada,no_aprobada',
            'fecha_resolucion'         => 'nullable|date',
            'nota_final'               => 'nullable|numeric|min:0|max:20',
        ]);

        $mp = MateriaPendiente::findOrFail($id);

        $anioActivo = \App\Models\AnioEscolar::vigente();
        if ($anioActivo && $mp->codigo_ano_escolar_origen === $anioActivo->codigo_ano_escolar) {
            return response()->json([
                'message' => 'No puedes modificar o mantener una materia pendiente que tenga el año escolar actual en curso. Las recuperaciones de este año se manejan en la grilla principal marcándolas en Revisión.'
            ], 422);
        }

        $valoresAnteriores = $mp->toArray();

        $mp->update($data);

        // Auditoría: UPDATE
        self::registrarAuditoria(
            'Materia_Pendiente',
            (string) $mp->id_materia_pendiente,
            'U',
            $valoresAnteriores,
            $mp->fresh()->toArray()
        );

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
        $valoresAnteriores = $mp->toArray();

        $mp->delete();

        // Auditoría: DELETE
        self::registrarAuditoria(
            'Materia_Pendiente',
            (string) $id,
            'D',
            $valoresAnteriores,
            null
        );

        return response()->json(['success' => true, 'message' => 'Materia pendiente eliminada.']);
    }
}
