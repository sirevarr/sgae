<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\FichaAntropometrica;
use App\Models\Traits\Auditable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    use Auditable;

    public function index(Request $request): JsonResponse
    {
        $q = Estudiante::with('matriculaActual.seccion.grado')
            ->when($request->filled('buscar'), fn ($query) => $query->buscar($request->buscar))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado_estudiante', $request->estado))
            ->orderBy('apellidos')
            ->paginate(25);

        return response()->json($q);
    }

    public function show(string $cedula): JsonResponse
    {
        return response()->json(
            Estudiante::with([
                'matriculas.seccion.grado',
                'matriculas.anioEscolar',
                'fichasAntropometricas',
                'materiasPendientes.materia',
            ])->findOrFail($cedula)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cedula_estudiante'    => 'required|string|max:20',
            'tipo_documento'       => 'required|string|max:10',
            'nacionalidad'         => 'nullable|string|max:30',
            'nombres'              => 'required|string|max:80',
            'apellidos'            => 'required|string|max:80',
            'genero'               => 'required|string|max:20',
            'fecha_nacimiento'     => 'nullable|date',
            'lugar_nacimiento'     => 'nullable|string|max:80',
            'estado_nacimiento'    => 'nullable|string|max:60',
            'municipio_nacimiento' => 'nullable|string|max:60',
            'direccion'            => 'nullable|string|max:200',
            'telefono'             => 'nullable|string|max:20',
            'correo'               => 'nullable|email|max:120',
            'condiciones_medicas'  => 'nullable|string',
            'medicamentos'         => 'nullable|string',
            'fecha_ingreso'        => 'nullable|date',
            'estado_estudiante'    => 'sometimes|string|in:activo,retirado,graduado',
        ]);

        if (Estudiante::where('cedula_estudiante', $data['cedula_estudiante'])->exists()) {
            return response()->json(['message' => 'La cédula del estudiante ya existe.'], 422);
        }

        $data['estado_estudiante'] ??= 'activo';
        $estudiante = Estudiante::create($data);

        // Auditoría: INSERT
        self::registrarAuditoria(
            'Estudiante',
            $estudiante->cedula_estudiante,
            'I',
            null,
            $estudiante->toArray()
        );

        return response()->json($estudiante, 201);
    }

    public function update(Request $request, string $cedula): JsonResponse
    {
        $estudiante = Estudiante::findOrFail($cedula);

        $data = $request->validate([
            'tipo_documento'       => 'sometimes|string|max:10',
            'nacionalidad'         => 'sometimes|nullable|string|max:30',
            'nombres'              => 'sometimes|string|max:80',
            'apellidos'            => 'sometimes|string|max:80',
            'genero'               => 'sometimes|string|max:20',
            'fecha_nacimiento'     => 'sometimes|nullable|date',
            'lugar_nacimiento'     => 'sometimes|nullable|string|max:80',
            'estado_nacimiento'    => 'sometimes|nullable|string|max:60',
            'municipio_nacimiento' => 'sometimes|nullable|string|max:60',
            'direccion'            => 'sometimes|nullable|string|max:200',
            'telefono'             => 'sometimes|nullable|string|max:20',
            'correo'               => 'sometimes|nullable|email|max:120',
            'condiciones_medicas'  => 'sometimes|nullable|string',
            'medicamentos'         => 'sometimes|nullable|string',
            'fecha_ingreso'        => 'sometimes|nullable|date',
            'estado_estudiante'    => 'sometimes|string|in:activo,retirado,graduado',
            'fecha_retiro'         => 'sometimes|nullable|date',
            'motivo_retiro'        => 'sometimes|nullable|string|max:200',
        ]);

        // Capturar valores anteriores antes del update
        $valoresAnteriores = $estudiante->toArray();

        $estudiante->update($data);

        // Auditoría: UPDATE
        self::registrarAuditoria(
            'Estudiante',
            $estudiante->cedula_estudiante,
            'U',
            $valoresAnteriores,
            $estudiante->fresh()->toArray()
        );

        return response()->json($estudiante->fresh());
    }

    public function fichaAntropometrica(Request $request, string $cedula): JsonResponse
    {
        Estudiante::findOrFail($cedula);

        $data = $request->validate([
            'codigo_ano_escolar' => 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
            'estatura'           => 'nullable|numeric|min:0.3|max:2.5',
            'peso'               => 'nullable|numeric|min:1|max:250',
            'talla_camisa'       => 'nullable|string|max:10',
            'talla_pantalon'     => 'nullable|string|max:10',
            'talla_zapatos'      => 'nullable|string|max:10',
            'fecha_medicion'     => 'nullable|date',
        ]);

        $ficha = FichaAntropometrica::updateOrCreate(
            ['cedula_estudiante' => $cedula, 'codigo_ano_escolar' => $data['codigo_ano_escolar']],
            $data
        );

        return response()->json($ficha, 201);
    }

    public function destroy(string $cedula): JsonResponse
    {
        $estudiante = Estudiante::findOrFail($cedula);

        // Capturar valores antes de eliminar
        $valoresAnteriores = $estudiante->toArray();

        try {
            $estudiante->delete();

            // Auditoría: DELETE
            self::registrarAuditoria(
                'Estudiante',
                $cedula,
                'D',
                $valoresAnteriores,
                null
            );

            return response()->json(null, 204);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['error' => 'No se puede eliminar el estudiante porque existen registros relacionados.'], 409);
        }
    }
}
