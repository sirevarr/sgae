<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institucion;
use App\Models\Personal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitucionController extends Controller
{
    /** GET /api/institucion — datos de la institución (primer registro) */
    public function show(): JsonResponse
    {
        if (! Institucion::tableExists()) {
            return response()->json(null);
        }

        $inst = Institucion::with(['director', 'coordinador'])->first();

        if (! $inst) {
            return response()->json(null);
        }

        return response()->json($inst);
    }

    /** PUT /api/institucion/{codigo_dea} — actualizar datos */
    public function update(Request $request, string $codigo_dea): JsonResponse
    {
        if (! Institucion::tableExists()) {
            return response()->json(['message' => 'La tabla de institución no está disponible en la base de datos actual.'], 500);
        }

        $inst = Institucion::find($codigo_dea);

        if (! $inst) {
            $inst = Institucion::create([
                'codigo_dea' => $codigo_dea,
                'nombre' => 'Institución sin nombre',
            ]);
        }

        $data = $request->validate([
            'nombre'                 => 'sometimes|string|max:150',
            'direccion'              => 'sometimes|nullable|string|max:200',
            'telefono'               => 'sometimes|nullable|string|max:20',
            'municipio'              => 'sometimes|nullable|string|max:80',
            'estado'                 => 'sometimes|nullable|string|max:80',
            'zona_educativa'         => 'sometimes|nullable|string|max:80',
            'director_actual'        => 'sometimes|nullable|integer|exists:Personal,cedula_personal',
            'coordinador_academico'  => 'sometimes|nullable|integer|exists:Personal,cedula_personal',
        ]);

        $inst->update($data);
        return response()->json($inst->fresh(['director', 'coordinador']));
    }

    public function destroy(string $codigo_dea): JsonResponse
    {
        $institucion = Institucion::findOrFail($codigo_dea);
        try {
            $institucion->delete();
            return response()->json(null, 204);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['error' => 'No se puede eliminar la institución porque tiene registros relacionados.'], 409);
        }
    }

    /** POST /api/institucion — crear datos de institución */
    public function store(Request $request): JsonResponse
    {
        if (! Institucion::tableExists()) {
            return response()->json(['message' => 'La tabla de institución no está disponible en la base de datos actual.'], 500);
        }

        $data = $request->validate([
            'codigo_dea'             => 'required|string|max:20|unique:Institucion,codigo_dea',
            'nombre'                 => 'required|string|max:150',
            'direccion'              => 'nullable|string|max:200',
            'telefono'               => 'nullable|string|max:20',
            'municipio'              => 'nullable|string|max:80',
            'estado'                 => 'nullable|string|max:80',
            'zona_educativa'         => 'nullable|string|max:80',
            'director_actual'        => 'nullable|integer|exists:Personal,cedula_personal',
            'coordinador_academico'  => 'nullable|integer|exists:Personal,cedula_personal',
        ]);

        $inst = Institucion::create($data);
        return response()->json($inst, 201);
    }

    /** GET /api/personal-lista — lista simplificada de personal para selects */
    public function personalLista(): JsonResponse
    {
        $lista = Personal::orderBy('apellidos')
            ->get(['cedula_personal', 'nombres', 'apellidos', 'cargo']);
        return response()->json($lista);
    }
}
