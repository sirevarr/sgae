<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Matricula;
use App\Models\Seccion;
use App\Models\Traits\Auditable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatriculaController extends Controller
{
    use Auditable;

    public function index(Request $request): JsonResponse
    {
        $q = $this->buildQuery($request)
            ->orderBy('numero_lista')
            ->paginate(30);

        return response()->json($q);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(
            Matricula::with([
                'estudiante',
                'seccion.grado',
                'anioEscolar',
                'representante',
            ])->findOrFail($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        // Convertir strings vacíos a null para campos opcionales
        $request->merge(array_map(fn ($v) => $v === '' ? null : $v, $request->only([
            'cedula_representante', 'numero_lista', 'procedencia',
            'ano_inicio_cursante', 'observaciones',
        ])));

        $data = $request->validate([
            'cedula_estudiante'    => 'required|string|exists:Estudiante,cedula_estudiante',
            'codigo_ano_escolar'   => 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
            'codigo_seccion'       => 'required|string|exists:Seccion,codigo_seccion',
            'cedula_representante' => 'nullable|integer|exists:Representante,cedula_representante',
            'fecha_matricula'      => 'required|date',
            'numero_lista'         => 'nullable|integer|min:1',
            'condicion_ingreso'    => 'required|string|max:50',
            'procedencia'          => 'nullable|string|max:300',
            'ano_inicio_cursante'  => 'nullable|integer|min:2000',
            'estado_matricula'     => 'sometimes|string|in:activa,retirada,trasladada',
            'observaciones'        => 'nullable|string',
        ]);

        $seccion = Seccion::findOrFail($data['codigo_seccion']);
        $capacidadResponse = $this->validarCapacidad($seccion);
        if ($capacidadResponse instanceof JsonResponse) {
            return $capacidadResponse;
        }

        if ($this->matriculaDuplicada($data)) {
            return response()->json([
                'error' => 'El estudiante ya tiene una matrícula activa en este año escolar.'
            ], 422);
        }

        $data['estado_matricula'] ??= 'activa';
        $matricula = Matricula::create($data);

        // Auditoría: INSERT
        self::registrarAuditoria(
            'Matricula',
            (string) $matricula->id_matricula,
            'I',
            null,
            $matricula->toArray()
        );

        return response()->json($matricula->load(['estudiante', 'seccion.grado']), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $matricula = Matricula::findOrFail($id);

        // Convertir strings vacíos a null para campos opcionales
        $request->merge(array_map(fn ($v) => $v === '' ? null : $v, $request->only([
            'cedula_representante', 'numero_lista', 'observaciones',
            'fecha_retiro', 'motivo_retiro',
        ])));

        $data = $request->validate([
            'codigo_seccion'       => 'sometimes|string|exists:Seccion,codigo_seccion',
            'cedula_representante' => 'sometimes|nullable|integer',
            'numero_lista'         => 'sometimes|nullable|integer',
            'condicion_ingreso'    => 'sometimes|string',
            'estado_matricula'     => 'sometimes|string|in:activa,retirada,trasladada',
            'observaciones'        => 'sometimes|nullable|string',
            'fecha_retiro'         => 'sometimes|nullable|date',
            'motivo_retiro'        => 'sometimes|nullable|string',
        ]);

        // Capturar valores anteriores
        $valoresAnteriores = $matricula->toArray();

        $matricula->update($data);

        // Auditoría: UPDATE
        self::registrarAuditoria(
            'Matricula',
            (string) $matricula->id_matricula,
            'U',
            $valoresAnteriores,
            $matricula->fresh()->toArray()
        );

        return response()->json($matricula->fresh(['estudiante', 'seccion.grado']));
    }

    private function buildQuery(Request $request)
    {
        return Matricula::with(['estudiante', 'seccion.grado', 'anioEscolar', 'representante'])
            ->when($request->filled('codigo_ano_escolar'), fn ($query) =>
                $query->where('codigo_ano_escolar', $request->codigo_ano_escolar)
            )
            ->when($request->filled('codigo_seccion'), fn ($query) =>
                $query->where('codigo_seccion', $request->codigo_seccion)
            )
            ->when($request->filled('estado_matricula'), fn ($query) =>
                $query->where('estado_matricula', $request->estado_matricula)
            )
            ->when($request->filled('buscar'), fn ($query) =>
                $query->whereHas('estudiante', fn ($q2) =>
                    $q2->where('nombres', 'like', "%{$request->buscar}%")
                        ->orWhere('apellidos', 'like', "%{$request->buscar}%")
                        ->orWhere('cedula_estudiante', 'like', "%{$request->buscar}%")
                )
            );
    }

    private function validarCapacidad(Seccion $seccion): ?JsonResponse
    {
        if ($seccion->capacidad_maxima && $seccion->total_estudiantes >= $seccion->capacidad_maxima) {
            return response()->json([
                'error' => "La sección {$seccion->codigo_seccion} está al máximo de su capacidad ({$seccion->capacidad_maxima} estudiantes)."
            ], 422);
        }

        return null;
    }

    private function matriculaDuplicada(array $data): bool
    {
        return Matricula::where('cedula_estudiante', $data['cedula_estudiante'])
            ->where('codigo_ano_escolar', $data['codigo_ano_escolar'])
            ->where('estado_matricula', 'activa')
            ->exists();
    }

    public function destroy(int $id): JsonResponse
    {
        $matricula = Matricula::findOrFail($id);

        // Capturar valores antes de eliminar
        $valoresAnteriores = $matricula->toArray();

        try {
            $matricula->delete();

            // Auditoría: DELETE
            self::registrarAuditoria(
                'Matricula',
                (string) $id,
                'D',
                $valoresAnteriores,
                null
            );

            return response()->json(['message' => 'Matrícula eliminada correctamente.']);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'No se puede eliminar la matrícula porque tiene evaluaciones o registros relacionados.',
                'error' => 'No se puede eliminar la matrícula porque tiene registros relacionados.'
            ], 409);
        }
    }
}
