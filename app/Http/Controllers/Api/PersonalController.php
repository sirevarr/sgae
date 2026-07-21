<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Personal;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! Personal::tableExists()) {
            return $this->tableUnavailableResponse('Personal');
        }

        $relations = [];
        if (Docente::tableExists()) {
            $relations[] = 'docente';
        }
        if (Usuario::tableExists()) {
            $relations[] = 'usuario';
        }

        $q = $this->buildQuery($request)
            ->with($relations)
            ->orderBy('apellidos')
            ->paginate(25);

        return response()->json($q);
    }

    public function show(int $cedula): JsonResponse
    {
        if (! Personal::tableExists()) {
            return $this->tableUnavailableResponse('Personal');
        }

        $relations = [];
        if (Docente::tableExists()) {
            $relations[] = 'docente';
        }
        if (Usuario::tableExists()) {
            $relations[] = 'usuario';
        }

        return response()->json(
            Personal::with($relations)->findOrFail($cedula)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cedula_personal'  => 'required|integer',
            'nombres'          => 'required|string|max:80',
            'apellidos'        => 'required|string|max:80',
            'cargo'            => 'required|string|max:60',
            'telefono'         => 'nullable|string|max:20',
            'correo'           => 'nullable|email|max:120',
            'fecha_nacimiento' => 'nullable|date',
            'genero'           => 'nullable|string|in:M,F',
            'fecha_ingreso'    => 'nullable|date',
            'estado'           => 'nullable|string|max:20',
            'observaciones'    => 'nullable|string',
            'especialidad'     => 'nullable|string|max:80',
            'turno'            => 'nullable|string|in:M,T,N',
        ]);

        if (! Personal::tableExists()) {
            return $this->tableUnavailableResponse('Personal');
        }

        if (Personal::where('cedula_personal', $data['cedula_personal'])->exists()) {
            return response()->json(['message' => 'La cédula del personal ya existe.'], 422);
        }

        $personal = Personal::create($data);

        $docentePayload = $this->buildDocentePayload($data);
        if ($docentePayload !== null && !empty($data['especialidad']) && Docente::tableExists()) {
            Docente::create(array_merge($docentePayload, [
                'cedula_personal' => $personal->cedula_personal,
            ]));
        }

        return response()->json($personal->load('docente'), 201);
    }

    public function update(Request $request, int $cedula): JsonResponse
    {
        if (! Personal::tableExists()) {
            return $this->tableUnavailableResponse('Personal');
        }

        $personal = Personal::findOrFail($cedula);

        $data = $request->validate([
            'nombres'          => 'sometimes|string|max:80',
            'apellidos'        => 'sometimes|string|max:80',
            'cargo'            => 'sometimes|string|max:60',
            'telefono'         => 'sometimes|nullable|string|max:20',
            'correo'           => 'sometimes|nullable|email|max:120',
            'fecha_nacimiento' => 'sometimes|nullable|date',
            'genero'           => 'sometimes|nullable|string|in:M,F',
            'fecha_ingreso'    => 'sometimes|nullable|date',
            'estado'           => 'sometimes|string|max:20',
            'observaciones'    => 'sometimes|nullable|string',
            // Docente
            'especialidad'     => 'sometimes|nullable|string|max:80',
            'turno'            => 'sometimes|nullable|string|in:M,T,N',
        ]);

        $personal->update($data);

        $docentePayload = $this->buildDocentePayload($data);
        if ($docentePayload !== null && Docente::tableExists()) {
            Docente::updateOrCreate(
                ['cedula_personal' => $cedula],
                $docentePayload
            );
        }

        return response()->json($personal->fresh(['docente']));
    }

    public function destroy(int $cedula): JsonResponse
    {
        if (! Personal::tableExists()) {
            return $this->tableUnavailableResponse('Personal');
        }

        $personal = Personal::findOrFail($cedula);
        $personal->delete();

        return response()->json(['message' => 'Personal eliminado.']);
    }

    private function buildQuery(Request $request)
    {
        $query = Personal::query();
        if (Docente::tableExists()) {
            $query->with('docente');
        }

        return $query
            ->when($request->filled('buscar'), fn ($query) =>
                $query->where('nombres', 'like', "%{$request->buscar}%")
                    ->orWhere('apellidos', 'like', "%{$request->buscar}%")
                    ->orWhere('cedula_personal', 'like', "%{$request->buscar}%")
            )
            ->when($request->filled('cargo'), fn ($query) =>
                $query->where('cargo', $request->cargo)
            );
    }

    private function buildDocentePayload(array $data): ?array
    {
        if (!array_key_exists('especialidad', $data)) {
            return null;
        }

        return [
            'especialidad' => $data['especialidad'] ?? null,
            'turno' => $data['turno'] ?? null,
        ];
    }

    private function tableUnavailableResponse(string $tableName): JsonResponse
    {
        return response()->json([
            'message' => "La tabla de {$tableName} no está disponible en la base de datos actual.",
        ], 500);
    }
}
