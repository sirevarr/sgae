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
        $inst = Institucion::with(['director', 'coordinador'])->first();
        return response()->json($inst);
    }

    /** PUT /api/institucion/{codigo_dea} — actualizar datos */
    public function update(Request $request, string $codigo_dea): JsonResponse
    {
        $inst = Institucion::findOrFail($codigo_dea);

        $data = $request->validate([
            'nombre'                 => 'sometimes|string|max:300',
            'direccion'              => 'sometimes|string|max:500',
            'telefono'               => 'sometimes|string|max:30',
            'municipio'              => 'sometimes|string|max:100',
            'estado'                 => 'sometimes|string|max:100',
            'zona_educativa'         => 'sometimes|string|max:100',
            'director_actual'        => 'sometimes|nullable|integer|exists:Personal,cedula_personal',
            'coordinador_academico'  => 'sometimes|nullable|integer|exists:Personal,cedula_personal',
        ]);

        $inst->update($data);
        return response()->json($inst->fresh(['director', 'coordinador']));
    }

    /** POST /api/institucion — crear datos de institución */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo_dea'             => 'required|string|max:50|unique:Institucion,codigo_dea',
            'nombre'                 => 'required|string|max:300',
            'direccion'              => 'nullable|string|max:500',
            'telefono'               => 'nullable|string|max:30',
            'municipio'              => 'nullable|string|max:100',
            'estado'                 => 'nullable|string|max:100',
            'zona_educativa'         => 'nullable|string|max:100',
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
