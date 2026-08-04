<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mencion;
use App\Models\Traits\Auditable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MencionController extends Controller
{
    use Auditable;

    public function index(): JsonResponse
    {
        return response()->json(Mencion::orderBy('nombre')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'estado' => 'sometimes|string|max:20',
        ]);

        if (Mencion::where('nombre', $data['nombre'])->exists()) {
            return response()->json(['message' => 'La mención ya existe.'], 422);
        }
        $mencion = Mencion::create($data);
        self::registrarAuditoria('Mencion', (string) $mencion->id_mencion, 'I', null, $mencion->toArray());
        return response()->json($mencion, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $mencion = Mencion::findOrFail($id);
        $valoresAnteriores = $mencion->toArray();
        $mencion->update($request->only(['nombre', 'estado']));
        self::registrarAuditoria('Mencion', (string) $id, 'U', $valoresAnteriores, $mencion->fresh()->toArray());
        return response()->json($mencion->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $mencion = Mencion::findOrFail($id);
        $valoresAnteriores = $mencion->toArray();
        try {
            $mencion->delete();
            self::registrarAuditoria('Mencion', (string) $id, 'D', $valoresAnteriores, null);
            return response()->json(['message' => 'Mención eliminada correctamente.']);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'No se puede eliminar la mención porque tiene registros asociados (secciones, plan de estudios o evaluaciones).',
                'error' => 'No se puede eliminar la mención porque tiene registros asociados.'
            ], 409);
        }
    }
}
