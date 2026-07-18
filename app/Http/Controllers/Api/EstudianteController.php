<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\FichaAntropometrica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Estudiante::with('matriculaActual.seccion.grado')
            ->when($request->buscar, fn($query) => $query->buscar($request->buscar))
            ->when($request->estado, fn($query) => $query->where('estado_estudiante', $request->estado))
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
            'cedula_estudiante'    => 'required|string|max:20|unique:Estudiante,cedula_estudiante',
            'tipo_documento'       => 'required|string|in:V,E',
            'nacionalidad'         => 'required|string|max:20',
            'nombres'              => 'required|string|max:200',
            'apellidos'            => 'required|string|max:200',
            'genero'               => 'required|string|in:M,F',
            'fecha_nacimiento'     => 'nullable|date',
            'lugar_nacimiento'     => 'nullable|string|max:200',
            'estado_nacimiento'    => 'nullable|string|max:100',
            'municipio_nacimiento' => 'nullable|string|max:100',
            'direccion'            => 'nullable|string|max:500',
            'telefono'             => 'nullable|string|max:30',
            'correo'               => 'nullable|email|max:200',
            'condiciones_medicas'  => 'nullable|string',
            'medicamentos'         => 'nullable|string',
            'fecha_ingreso'        => 'nullable|date',
            'estado_estudiante'    => 'sometimes|string|in:activo,retirado,graduado,trasladado',
        ]);

        $data['estado_estudiante'] ??= 'activo';
        return response()->json(Estudiante::create($data), 201);
    }

    public function update(Request $request, string $cedula): JsonResponse
    {
        $estudiante = Estudiante::findOrFail($cedula);

        $data = $request->validate([
            'nombres'              => 'sometimes|string|max:200',
            'apellidos'            => 'sometimes|string|max:200',
            'genero'               => 'sometimes|string|in:M,F',
            'fecha_nacimiento'     => 'sometimes|nullable|date',
            'lugar_nacimiento'     => 'sometimes|nullable|string',
            'estado_nacimiento'    => 'sometimes|nullable|string',
            'municipio_nacimiento' => 'sometimes|nullable|string',
            'direccion'            => 'sometimes|nullable|string',
            'telefono'             => 'sometimes|nullable|string|max:30',
            'correo'               => 'sometimes|nullable|email',
            'condiciones_medicas'  => 'sometimes|nullable|string',
            'medicamentos'         => 'sometimes|nullable|string',
            'estado_estudiante'    => 'sometimes|string',
            'fecha_retiro'         => 'sometimes|nullable|date',
            'motivo_retiro'        => 'sometimes|nullable|string',
        ]);

        $estudiante->update($data);
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
}