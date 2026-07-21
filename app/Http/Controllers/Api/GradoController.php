<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradoController extends Controller
{
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
        return response()->json(Grado::create($data), 201);
    }

    public function update(Request $request, string $codigo): JsonResponse
    {
        $grado = Grado::findOrFail($codigo);
        $grado->update($request->only(['nombre', 'nivel_educativo', 'numero_ano', 'estado']));
        return response()->json($grado->fresh());
    }

    public function destroy(string $codigo): JsonResponse
    {
        Grado::findOrFail($codigo)->delete();
        return response()->json(['message' => 'Grado eliminado.']);
    }
}
