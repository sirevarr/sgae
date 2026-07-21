<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InscripcionController extends Controller
{
    private const ERROR_INSCRIPCION_NO_ENCONTRADA = 'Inscripción no encontrada';
    private const ERROR_INSCRIPCION_DUPLICADA = 'El estudiante ya tiene una inscripción activa en este año escolar.';
    private const ERROR_ESTUDIANTE_INACTIVO = 'No se puede inscribir a un estudiante inactivo.';

    public function index(Request $request): JsonResponse
    {
        $query = Inscripcion::with(['estudiante', 'seccion.grado', 'anioEscolar', 'representante'])
            ->latest('fecha_matricula');

        if ($request->filled('periodo')) {
            $query->where('codigo_ano_escolar', $request->periodo);
        }

        if ($request->filled('codigo_ano_escolar')) {
            $query->where('codigo_ano_escolar', $request->codigo_ano_escolar);
        }

        if ($request->filled('codigo_seccion')) {
            $query->where('codigo_seccion', $request->codigo_seccion);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'estudiante_id' => 'required|string|exists:Estudiante,cedula_estudiante',
            'periodo' => 'required_without:codigo_ano_escolar|string',
            'codigo_ano_escolar' => 'sometimes|string|exists:Anio_Escolar,codigo_ano_escolar',
            'seccion' => 'required_without:codigo_seccion|string',
            'codigo_seccion' => 'sometimes|string|exists:Seccion,codigo_seccion',
            'cedula_representante' => 'nullable|integer|exists:Representante,cedula_representante',
            'fecha_inscripcion' => 'required_without:fecha_matricula|date',
            'fecha_matricula' => 'sometimes|date',
            'numero_lista' => 'nullable|integer|min:1',
            'condicion_ingreso' => 'nullable|string',
            'procedencia' => 'nullable|string|max:300',
            'ano_inicio_cursante' => 'nullable|integer|min:2000',
            'estado_matricula' => 'sometimes|string|in:activa,retirada,trasladada',
            'estado' => 'sometimes|string',
            'observaciones' => 'nullable|string',
        ]);

        $payload = [
            'cedula_estudiante' => $data['estudiante_id'],
            'codigo_ano_escolar' => $data['codigo_ano_escolar'] ?? $data['periodo'],
            'codigo_seccion' => $data['codigo_seccion'] ?? $data['seccion'],
            'cedula_representante' => $data['cedula_representante'] ?? null,
            'fecha_matricula' => $data['fecha_matricula'] ?? $data['fecha_inscripcion'],
            'numero_lista' => $data['numero_lista'] ?? null,
            'condicion_ingreso' => $data['condicion_ingreso'] ?? null,
            'procedencia' => $data['procedencia'] ?? null,
            'ano_inicio_cursante' => $data['ano_inicio_cursante'] ?? null,
            'estado_matricula' => $data['estado_matricula'] ?? ($data['estado'] ?? 'activa'),
            'observaciones' => $data['observaciones'] ?? null,
        ];

        $estudiante = Estudiante::where('cedula_estudiante', $payload['cedula_estudiante'])->first();
        if ($this->estudianteInactivo($estudiante)) {
            return response()->json(['success' => false, 'error' => self::ERROR_ESTUDIANTE_INACTIVO], 422);
        }

        if ($this->inscripcionDuplicada($payload['cedula_estudiante'], $payload['codigo_ano_escolar'])) {
            return response()->json(['success' => false, 'error' => self::ERROR_INSCRIPCION_DUPLICADA], 422);
        }

        $inscripcion = Inscripcion::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Inscripción creada con éxito',
            'data' => $inscripcion->load(['estudiante', 'seccion.grado', 'anioEscolar', 'representante']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $inscripcion = Inscripcion::with(['estudiante', 'seccion.grado', 'anioEscolar', 'representante'])->find($id);

        if (!$inscripcion) {
            return $this->respuestaNoEncontrada(self::ERROR_INSCRIPCION_NO_ENCONTRADA);
        }

        return response()->json([
            'success' => true,
            'data' => $inscripcion,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $inscripcion = Inscripcion::find($id);

        if (!$inscripcion) {
            return $this->respuestaNoEncontrada(self::ERROR_INSCRIPCION_NO_ENCONTRADA);
        }

        $data = $request->validate([
            'periodo' => 'sometimes|string',
            'codigo_ano_escolar' => 'sometimes|string|exists:Anio_Escolar,codigo_ano_escolar',
            'seccion' => 'sometimes|string',
            'codigo_seccion' => 'sometimes|string|exists:Seccion,codigo_seccion',
            'cedula_representante' => 'sometimes|nullable|integer|exists:Representante,cedula_representante',
            'fecha_inscripcion' => 'sometimes|date',
            'fecha_matricula' => 'sometimes|date',
            'numero_lista' => 'sometimes|nullable|integer|min:1',
            'condicion_ingreso' => 'sometimes|string',
            'procedencia' => 'sometimes|nullable|string|max:300',
            'ano_inicio_cursante' => 'sometimes|nullable|integer|min:2000',
            'estado_matricula' => 'sometimes|string|in:activa,retirada,trasladada',
            'estado' => 'sometimes|string',
            'observaciones' => 'sometimes|nullable|string',
        ]);

        $payload = [];
        if (array_key_exists('periodo', $data)) {
            $payload['codigo_ano_escolar'] = $data['periodo'];
        }
        if (array_key_exists('codigo_ano_escolar', $data)) {
            $payload['codigo_ano_escolar'] = $data['codigo_ano_escolar'];
        }
        if (array_key_exists('seccion', $data)) {
            $payload['codigo_seccion'] = $data['seccion'];
        }
        if (array_key_exists('codigo_seccion', $data)) {
            $payload['codigo_seccion'] = $data['codigo_seccion'];
        }
        if (array_key_exists('fecha_inscripcion', $data)) {
            $payload['fecha_matricula'] = $data['fecha_inscripcion'];
        }
        if (array_key_exists('fecha_matricula', $data)) {
            $payload['fecha_matricula'] = $data['fecha_matricula'];
        }
        foreach (['cedula_representante', 'numero_lista', 'condicion_ingreso', 'procedencia', 'ano_inicio_cursante', 'estado_matricula', 'estado', 'observaciones'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        $inscripcion->update($payload);
        $inscripcion->load(['estudiante', 'seccion.grado', 'anioEscolar', 'representante']);

        return response()->json([
            'success' => true,
            'message' => 'Inscripción actualizada con éxito',
            'data' => $inscripcion,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $inscripcion = Inscripcion::find($id);

        if (!$inscripcion) {
            return $this->respuestaNoEncontrada(self::ERROR_INSCRIPCION_NO_ENCONTRADA);
        }

        $inscripcion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inscripción eliminada con éxito',
        ]);
    }

    private function respuestaNoEncontrada(string $mensaje): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $mensaje,
        ], 404);
    }

    private function inscripcionDuplicada(string $cedulaEstudiante, string $codigoAnoEscolar): bool
    {
        return Inscripcion::where('cedula_estudiante', $cedulaEstudiante)
            ->where('codigo_ano_escolar', $codigoAnoEscolar)
            ->where('estado_matricula', 'activa')
            ->exists();
    }

    private function estudianteInactivo(?Estudiante $estudiante): bool
    {
        return $estudiante !== null
            && !Str::startsWith(strtolower((string) ($estudiante->estado_estudiante ?? '')), 'act');
    }
}
