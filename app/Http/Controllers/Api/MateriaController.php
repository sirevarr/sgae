<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
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
            'nombre'         => 'required|string|max:200',
            'area_formacion' => 'nullable|string|max:100',
        ]);
        return response()->json(Materia::create($data), 201);
    }

    public function update(Request $request, string $siglas): JsonResponse
    {
        $materia = Materia::findOrFail($siglas);
        $materia->update($request->only(['nombre', 'area_formacion']));
        return response()->json($materia->fresh());
    }

    public function destroy(string $siglas): JsonResponse
    {
        Materia::findOrFail($siglas)->delete();
        return response()->json(['message' => 'Materia eliminada.']);
    }
}