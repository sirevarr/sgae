<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Personal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Personal::with('docente')
            ->when($request->buscar, fn($query) =>
                $query->where('nombres', 'like', "%{$request->buscar}%")
                      ->orWhere('apellidos', 'like', "%{$request->buscar}%")
                      ->orWhere('cedula_personal', 'like', "%{$request->buscar}%")
            )
            ->when($request->cargo, fn($query) =>
                $query->where('cargo', $request->cargo)
            )
            ->orderBy('apellidos')
            ->paginate(25);

        return response()->json($q);
    }

    public function show(int $cedula): JsonResponse
    {
        return response()->json(
            Personal::with(['docente', 'usuario'])->findOrFail($cedula)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cedula_personal'  => 'required|integer|unique:Personal,cedula_personal',
            'nombres'          => 'required|string|max:200',
            'apellidos'        => 'required|string|max:200',
            'cargo'            => 'required|string|max:100',
            'telefono'         => 'nullable|string|max:30',
            'correo'           => 'nullable|email|max:200',
            'fecha_nacimiento' => 'nullable|date',
            'genero'           => 'nullable|string|max:20',
            'fecha_ingreso'    => 'nullable|date',
            'estado'           => 'nullable|string|max:20',
            'observaciones'    => 'nullable|string',
            // Campos de docente (opcionales)
            'especialidad'     => 'nullable|string|max:200',
            'turno'            => 'nullable|string|max:20',
        ]);

        $personal = Personal::create($data);

        // Si tiene especialidad, crear registro en Docente
        if (!empty($data['especialidad'])) {
            Docente::create([
                'cedula_personal' => $personal->cedula_personal,
                'especialidad'    => $data['especialidad'],
                'turno'           => $data['turno'] ?? null,
            ]);
        }

        return response()->json($personal->load('docente'), 201);
    }

    public function update(Request $request, int $cedula): JsonResponse
    {
        $personal = Personal::findOrFail($cedula);

        $data = $request->validate([
            'nombres'          => 'sometimes|string|max:200',
            'apellidos'        => 'sometimes|string|max:200',
            'cargo'            => 'sometimes|string|max:100',
            'telefono'         => 'sometimes|nullable|string|max:30',
            'correo'           => 'sometimes|nullable|email|max:200',
            'fecha_nacimiento' => 'sometimes|nullable|date',
            'genero'           => 'sometimes|nullable|string|max:20',
            'fecha_ingreso'    => 'sometimes|nullable|date',
            'estado'           => 'sometimes|string|max:20',
            'observaciones'    => 'sometimes|nullable|string',
            // Docente
            'especialidad'     => 'sometimes|nullable|string|max:200',
            'turno'            => 'sometimes|nullable|string|max:20',
        ]);

        $personal->update($data);

        // Actualizar o crear Docente si se envía especialidad
        if (array_key_exists('especialidad', $data)) {
            Docente::updateOrCreate(
                ['cedula_personal' => $cedula],
                ['especialidad' => $data['especialidad'], 'turno' => $data['turno'] ?? null]
            );
        }

        return response()->json($personal->fresh(['docente']));
    }

    public function destroy(int $cedula): JsonResponse
    {
        $personal = Personal::findOrFail($cedula);
        $personal->delete();
        return response()->json(['message' => 'Personal eliminado.']);
    }
}
