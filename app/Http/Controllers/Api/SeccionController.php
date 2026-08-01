<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seccion;
use App\Models\AsignacionDocente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeccionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = $this->buildQuery($request)
            ->orderBy('codigo_grado')
            ->orderBy('letra')
            ->get();

        return response()->json($q);
    }

    public function show(string $codigo): JsonResponse
    {
        $seccion = Seccion::with([
            'grado', 'mencion', 'docenteGuia.personal',
            'anioEscolar',
            'asignaciones.docente.personal',
            'asignaciones.materia',
            'matriculas.estudiante',
        ])->findOrFail($codigo);

        return response()->json($seccion);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo_seccion'      => 'required|string|max:15|unique:Seccion,codigo_seccion',
            'letra'               => 'required|string|max:1',
            'codigo_grado'        => 'required|string|exists:Grado,codigo_grado',
            'codigo_ano_escolar'  => 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
            'id_mencion'          => 'nullable|integer|exists:Mencion,id_mencion',
            'cedula_docente_guia' => 'nullable|integer|exists:Docente,cedula_personal',
            'capacidad_maxima'    => 'required|integer|min:1|max:60',
            'turno'               => 'required|string|max:30',
            'aula_asignada'       => 'nullable|string|max:40',
        ]);

        return response()->json(
            Seccion::create($data)->load(['grado', 'mencion']), 201
        );
    }

    public function update(Request $request, string $codigo): JsonResponse
    {
        $seccion = Seccion::findOrFail($codigo);
        $data = $request->validate([
            'letra'               => 'sometimes|string|max:1',
            'id_mencion'          => 'sometimes|nullable|integer|exists:Mencion,id_mencion',
            'cedula_docente_guia' => 'sometimes|nullable|integer|exists:Docente,cedula_personal',
            'capacidad_maxima'    => 'sometimes|integer|min:1|max:60',
            'turno'               => 'sometimes|string|max:30',
            'aula_asignada'       => 'sometimes|nullable|string|max:40',
        ]);
        $seccion->update($data);
        return response()->json($seccion->fresh(['grado', 'mencion', 'docenteGuia.personal']));
    }

    public function destroy(string $codigo): JsonResponse
    {
        $seccion = Seccion::findOrFail($codigo);
        try {
            $seccion->delete();
            return response()->json(['message' => 'Sección eliminada.']);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'error' => 'No se puede eliminar la sección porque tiene registros asociados (matrículas, asignaciones o evaluaciones).'
            ], 409);
        }
    }

    /** POST /api/secciones/{codigo}/asignaciones — asignar docente a materia en sección */
    public function asignarDocente(Request $request, string $codigo): JsonResponse
    {
        Seccion::findOrFail($codigo);
        $data = $request->validate([
            'cedula_docente'     => 'required|integer|exists:Docente,cedula_personal',
            'siglas_materia'     => 'required|string|exists:Materia,siglas',
            'id_mencion'         => 'nullable|integer|exists:Mencion,id_mencion',
            'codigo_grado'       => 'required|string|exists:Grado,codigo_grado',
            'codigo_ano_escolar' => 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
            'horas_asignadas'    => 'nullable|integer|min:1',
        ]);

        $asignacion = AsignacionDocente::updateOrCreate(
            [
                'codigo_seccion' => $codigo,
                'siglas_materia' => $data['siglas_materia'],
                'codigo_ano_escolar' => $data['codigo_ano_escolar'],
            ],
            [
                'cedula_docente'  => $data['cedula_docente'],
                'id_mencion'      => $data['id_mencion'],
                'codigo_grado'    => $data['codigo_grado'],
                'horas_asignadas' => $data['horas_asignadas'] ?? null,
            ]
        );

        return response()->json($asignacion->load(['docente.personal', 'materia']), 201);
    }

    private function buildQuery(Request $request)
    {
        return Seccion::with(['grado', 'mencion', 'docenteGuia.personal', 'anioEscolar'])
            ->when($request->filled('codigo_ano_escolar'), fn ($query) =>
                $query->where('codigo_ano_escolar', $request->codigo_ano_escolar)
            )
            ->when($request->filled('codigo_grado'), fn ($query) =>
                $query->where('codigo_grado', $request->codigo_grado)
            );
    }
}
