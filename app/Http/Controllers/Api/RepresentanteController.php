<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Representante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepresentanteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Representante::when($request->buscar, fn($query) =>
            $query->where('nombres', 'like', "%{$request->buscar}%")
                  ->orWhere('apellidos', 'like', "%{$request->buscar}%")
                  ->orWhere('cedula_representante', 'like', "%{$request->buscar}%")
        )->orderBy('apellidos')->paginate(25);

        return response()->json($q);
    }

    public function show(int $cedula): JsonResponse
    {
        return response()->json(
            Representante::with('matriculas.estudiante')->findOrFail($cedula)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cedula_representante'  => 'required|integer|unique:Representante,cedula_representante',
            'nacionalidad'          => 'required|string|max:20',
            'nombres'               => 'required|string|max:200',
            'apellidos'             => 'required|string|max:200',
            'parentesco'            => 'required|string|max:50',
            'ocupacion'             => 'nullable|string|max:200',
            'direccion'             => 'nullable|string|max:500',
            'telefono'              => 'nullable|string|max:30',
            'correo'                => 'nullable|email|max:200',
            'es_representante_legal' => 'sometimes|boolean',
        ]);

        return response()->json(Representante::create($data), 201);
    }

    public function update(Request $request, int $cedula): JsonResponse
    {
        $rep = Representante::findOrFail($cedula);
        $data = $request->validate([
            'nombres'               => 'sometimes|string|max:200',
            'apellidos'             => 'sometimes|string|max:200',
            'parentesco'            => 'sometimes|string|max:50',
            'ocupacion'             => 'sometimes|nullable|string|max:200',
            'direccion'             => 'sometimes|nullable|string|max:500',
            'telefono'              => 'sometimes|nullable|string|max:30',
            'correo'                => 'sometimes|nullable|email|max:200',
            'es_representante_legal' => 'sometimes|boolean',
        ]);
        $rep->update($data);
        return response()->json($rep->fresh());
    }
}
