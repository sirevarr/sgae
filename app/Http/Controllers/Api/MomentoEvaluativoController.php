<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MomentoEvaluativo;
use App\Models\AnioEscolar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MomentoEvaluativoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = MomentoEvaluativo::with('anioEscolar')
            ->when($request->codigo_ano_escolar, fn($query) =>
                $query->where('codigo_ano_escolar', $request->codigo_ano_escolar)
            )
            ->orderBy('numero_momento')
            ->get();

        return response()->json($q);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'numero_momento'     => 'required|integer|in:1,2,3',
            'codigo_ano_escolar' => 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
            'nombre'             => 'required|string|max:100',
            'fecha_inicio'       => 'nullable|date',
            'fecha_fin'          => 'nullable|date|after_or_equal:fecha_inicio',
            'porcentaje'         => 'nullable|numeric|min:0|max:100',
            'estado'             => 'required|in:activo,finalizado,por_iniciar',
        ]);

        $existe = MomentoEvaluativo::where([
            'numero_momento'     => $data['numero_momento'],
            'codigo_ano_escolar' => $data['codigo_ano_escolar'],
        ])->exists();

        if ($existe) {
            return response()->json(['error' => 'Este momento ya existe para el año escolar indicado.'], 422);
        }

        // Solo un momento puede estar activo a la vez
        if ($data['estado'] === 'activo') {
            MomentoEvaluativo::where('codigo_ano_escolar', $data['codigo_ano_escolar'])
                ->where('estado', 'activo')
                ->update(['estado' => 'finalizado']);
        }

        return response()->json(MomentoEvaluativo::create($data), 201);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'numero_momento'     => 'required|integer',
            'codigo_ano_escolar' => 'required|string',
            'nombre'             => 'sometimes|string|max:100',
            'fecha_inicio'       => 'sometimes|nullable|date',
            'fecha_fin'          => 'sometimes|nullable|date',
            'porcentaje'         => 'sometimes|nullable|numeric|min:0|max:100',
            'estado'             => 'sometimes|in:activo,finalizado,por_iniciar',
        ]);

        if (($data['estado'] ?? null) === 'activo') {
            MomentoEvaluativo::where('codigo_ano_escolar', $data['codigo_ano_escolar'])
                ->where('numero_momento', '!=', $data['numero_momento'])
                ->where('estado', 'activo')
                ->update(['estado' => 'finalizado']);
        }

        MomentoEvaluativo::where([
            'numero_momento'     => $data['numero_momento'],
            'codigo_ano_escolar' => $data['codigo_ano_escolar'],
        ])->update($request->only(['nombre', 'fecha_inicio', 'fecha_fin', 'porcentaje', 'estado']));

        return response()->json(['message' => 'Momento actualizado.']);
    }
}
