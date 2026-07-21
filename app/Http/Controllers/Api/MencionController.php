<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mencion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MencionController extends Controller
{
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
        return response()->json(Mencion::create($data), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $mencion = Mencion::findOrFail($id);
        $mencion->update($request->only(['nombre', 'estado']));
        return response()->json($mencion->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        Mencion::findOrFail($id)->delete();
        return response()->json(['message' => 'Mención eliminada.']);
    }
}
