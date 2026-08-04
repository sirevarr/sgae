<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grado;
use App\Models\Traits\Auditable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradoController extends Controller
{
    use Auditable;

    public function index(): JsonResponse
    {
        return response()->json(Grado::orderBy('numero_ano')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo_grado'    => 'required|string|max:10',
            'nombre'          => 'required|string|max:60',
            'nivel_educativo' => 'required|string|max:40',
            'numero_ano'      => 'required|integer|min:1|max:6',
            'estado'          => 'sometimes|string|max:20',
        ]);

        $exists = Grado::where('codigo_grado', $data['codigo_grado'])->exists();
        if ($exists) {
            return response()->json(['message' => 'El código de grado ya existe.'], 422);
        }
        $grado = Grado::create($data);
        self::registrarAuditoria('Grado', $grado->codigo_grado, 'I', null, $grado->toArray());
        return response()->json($grado, 201);
    }

    public function update(Request $request, string $codigo): JsonResponse
    {
        $grado = Grado::findOrFail($codigo);
        $valoresAnteriores = $grado->toArray();
        $data = $request->validate([
            'nombre'          => 'sometimes|string|max:60',
            'nivel_educativo' => 'sometimes|string|max:40',
            'numero_ano'      => 'sometimes|integer|min:1|max:6',
            'estado'          => 'sometimes|string|max:20',
        ]);
        $grado->update($data);
        self::registrarAuditoria('Grado', $codigo, 'U', $valoresAnteriores, $grado->fresh()->toArray());
        return response()->json($grado->fresh());
    }

    public function destroy(string $codigo): JsonResponse
    {
        $grado = Grado::findOrFail($codigo);
        $valoresAnteriores = $grado->toArray();
        try {
            $grado->delete();
            self::registrarAuditoria('Grado', $codigo, 'D', $valoresAnteriores, null);
            return response()->json(['message' => 'Grado eliminado correctamente.']);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'No se puede eliminar el grado porque tiene registros asociados (secciones o plan de estudios).',
                'error' => 'No se puede eliminar el grado porque tiene registros asociados.'
            ], 409);
        }
    }
}
