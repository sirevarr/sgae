<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use App\Models\Traits\Auditable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    use Auditable;

    public function index(): JsonResponse
    {
        return response()->json(Materia::orderBy('nombre')->get());
    }

    public function show(string $siglas): JsonResponse
    {
        return response()->json(Materia::findOrFail($siglas));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'siglas'         => 'required|string|max:10|unique:Materia,siglas',
            'nombre'         => 'required|string|max:80',
            'area_formacion' => 'nullable|string|max:80',
        ]);
        $materia = Materia::create($data);
        self::registrarAuditoria('Materia', $materia->siglas, 'I', null, $materia->toArray());
        return response()->json($materia, 201);
    }

    public function update(Request $request, string $siglas): JsonResponse
    {
        $materia = Materia::findOrFail($siglas);
        $valoresAnteriores = $materia->toArray();
        $materia->update($request->only(['nombre', 'area_formacion']));
        self::registrarAuditoria('Materia', $siglas, 'U', $valoresAnteriores, $materia->fresh()->toArray());
        return response()->json($materia->fresh());
    }

    public function destroy(string $siglas): JsonResponse
    {
        $materia = Materia::findOrFail($siglas);
        $valoresAnteriores = $materia->toArray();
        try {
            $materia->delete();
            self::registrarAuditoria('Materia', $siglas, 'D', $valoresAnteriores, null);
            return response()->json(['message' => 'Materia eliminada correctamente.']);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'No se puede eliminar la materia porque tiene registros asociados (plan de estudios, asignaciones o evaluaciones).',
                'error' => 'No se puede eliminar la materia porque tiene registros asociados.'
            ], 409);
        }
    }
}