<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanEstudios;
use App\Models\Traits\Auditable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanEstudiosController extends Controller
{
    use Auditable;

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->buildQuery($request)->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'siglas_materia'     => 'required|string|exists:Materia,siglas',
            'id_mencion'         => 'required|integer|exists:Mencion,id_mencion',
            'codigo_grado'       => 'required|string|exists:Grado,codigo_grado',
            'codigo_ano_escolar' => 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
            'horas_semanales'    => 'nullable|integer|min:1',
            'obligatoria'        => 'sometimes|boolean',
            'tipo_evaluacion'    => 'sometimes|string|in:N,L',
            'se_repara'          => 'sometimes|boolean',
            'creditos'           => 'nullable|integer',
            'estado'             => 'sometimes|string|max:20',
        ]);

        // PK compuesta: verificar unicidad
        $existe = PlanEstudios::where($this->buildPlanKey($data))->exists();

        if ($existe) {
            return response()->json(['error' => 'Esta combinación ya existe en el plan de estudios.'], 422);
        }

        $plan = PlanEstudios::create($data);
        $idStr = json_encode($this->buildPlanKey($data));
        self::registrarAuditoria('Plan_Estudios', $idStr, 'I', null, $plan->toArray());
        return response()->json($plan->load(['materia', 'mencion', 'grado']), 201);
    }

    public function update(Request $request): JsonResponse
    {
        $keys = $request->validate([
            'siglas_materia'     => 'required|string',
            'id_mencion'         => 'required|integer',
            'codigo_grado'       => 'required|string',
            'codigo_ano_escolar' => 'required|string',
            'horas_semanales'    => 'sometimes|integer',
            'obligatoria'        => 'sometimes|boolean',
            'tipo_evaluacion'    => 'sometimes|string|in:N,L',
            'se_repara'          => 'sometimes|boolean',
            'creditos'           => 'sometimes|nullable|integer',
            'estado'             => 'sometimes|string|max:20',
        ]);

        $plan = PlanEstudios::where($this->buildPlanKey($keys))->first();
        $valoresAnteriores = $plan ? $plan->toArray() : [];

        PlanEstudios::where($this->buildPlanKey($keys))
            ->update($request->only([
                'horas_semanales', 'obligatoria', 'tipo_evaluacion', 'se_repara', 'creditos', 'estado'
            ]));

        if ($plan) {
            $idStr = json_encode($this->buildPlanKey($keys));
            self::registrarAuditoria('Plan_Estudios', $idStr, 'U', $valoresAnteriores, $plan->fresh()->toArray());
        }

        return response()->json(['message' => 'Plan actualizado.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $keys = $request->validate([
            'siglas_materia'     => 'required|string',
            'id_mencion'         => 'required|integer',
            'codigo_grado'       => 'required|string',
            'codigo_ano_escolar' => 'required|string',
        ]);

        $plan = PlanEstudios::where($this->buildPlanKey($keys))->first();
        $valoresAnteriores = $plan ? $plan->toArray() : [];

        PlanEstudios::where($this->buildPlanKey($keys))->delete();

        if ($plan) {
            $idStr = json_encode($this->buildPlanKey($keys));
            self::registrarAuditoria('Plan_Estudios', $idStr, 'D', $valoresAnteriores, null);
        }

        return response()->json(['message' => 'Entrada del plan eliminada.']);
    }

    private function buildQuery(Request $request)
    {
        return PlanEstudios::with(['materia', 'mencion', 'grado', 'anioEscolar'])
            ->when($request->filled('codigo_ano_escolar'), fn ($query) =>
                $query->where('codigo_ano_escolar', $request->codigo_ano_escolar)
            )
            ->when($request->filled('codigo_grado'), fn ($query) =>
                $query->where('codigo_grado', $request->codigo_grado)
            )
            ->when($request->filled('id_mencion'), fn ($query) =>
                $query->where('id_mencion', $request->id_mencion)
            );
    }

    private function buildPlanKey(array $data): array
    {
        return [
            'siglas_materia'     => $data['siglas_materia'],
            'id_mencion'         => $data['id_mencion'],
            'codigo_grado'       => $data['codigo_grado'],
            'codigo_ano_escolar' => $data['codigo_ano_escolar'],
        ];
    }
}
