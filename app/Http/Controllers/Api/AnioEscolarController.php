<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnioEscolar;
use App\Models\AsignacionDocente;
use App\Models\PlanEstudios;
use App\Models\Seccion;
use App\Models\Traits\Auditable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnioEscolarController extends Controller
{
    use Auditable;

    private const ESTADO_VIGENTE = 'vigente';

    public function index(): JsonResponse
    {
        return response()->json(
            AnioEscolar::orderByDesc('codigo_ano_escolar')->get()
        );
    }

    public function show(string $codigo): JsonResponse
    {
        return response()->json(
            AnioEscolar::with(['secciones', 'momentos'])->findOrFail($codigo)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo_ano_escolar' => 'required|string|max:10|unique:Anio_Escolar,codigo_ano_escolar',
            'fecha_inicio'       => 'nullable|date',
            'fecha_fin'          => 'nullable|date|after_or_equal:fecha_inicio',
            'estado'             => 'required|in:vigente,cerrado,planificado',
        ]);

        $this->sincronizarEstadoVigente($data);

        return response()->json(AnioEscolar::create($data), 201);
    }

    public function update(Request $request, string $codigo): JsonResponse
    {
        $anio = AnioEscolar::findOrFail($codigo);

        $data = $request->validate([
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin'    => 'sometimes|date',
            'estado'       => 'sometimes|in:vigente,cerrado,planificado',
        ]);

        $this->sincronizarEstadoVigente($data, $codigo);

        $anio->update($data);

        return response()->json($anio->fresh());
    }

    public function vigente(): JsonResponse
    {
        return response()->json(AnioEscolar::vigente());
    }

    public function destroy(string $codigo): JsonResponse
    {
        $anio = AnioEscolar::findOrFail($codigo);
        try {
            $anio->delete();
            return response()->json(null, 204);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['error' => 'No se puede eliminar el año escolar porque existen registros relacionados.'], 409);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  PUNTO 6 — COPIAR CONFIGURACIÓN DEL AÑO ANTERIOR
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Vista previa de lo que se copiará del año origen al año destino.
     * No modifica la base de datos.
     *
     * GET /api/anios-escolares/copiar-config/preview
     *   ?codigo_ano_origen=2025-2026&codigo_ano_destino=2026-2027
     */
    public function previsualizarCopia(Request $request): JsonResponse
    {
        $request->validate([
            'codigo_ano_origen'  => 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
            'codigo_ano_destino' => 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
        ]);

        $origen  = $request->codigo_ano_origen;
        $destino = $request->codigo_ano_destino;

        if ($origen === $destino) {
            return response()->json(['error' => 'El año origen y destino no pueden ser iguales.'], 422);
        }

        // Datos del año origen
        $planOrigen      = PlanEstudios::with('materia')
            ->where('codigo_ano_escolar', $origen)->get();
        $seccionesOrigen = Seccion::with(['grado', 'mencion', 'docenteGuia.personal'])
            ->where('codigo_ano_escolar', $origen)
            ->orderBy('codigo_grado')->orderBy('letra')->get();
        $asigOrigen      = AsignacionDocente::with(['docente.personal', 'materia', 'seccion.grado'])
            ->where('codigo_ano_escolar', $origen)->get();

        // Datos ya existentes en el destino (para advertir al coordinador)
        $planDestino      = PlanEstudios::where('codigo_ano_escolar', $destino)->count();
        $seccionesDestino = Seccion::where('codigo_ano_escolar', $destino)->count();
        $asigDestino      = AsignacionDocente::where('codigo_ano_escolar', $destino)->count();

        return response()->json([
            'origen'  => $origen,
            'destino' => $destino,
            'plan_estudios' => [
                'total' => $planOrigen->count(),
                'filas' => $planOrigen->map(fn ($pe) => [
                    'siglas_materia'  => $pe->siglas_materia,
                    'nombre_materia'  => $pe->materia->nombre ?? $pe->siglas_materia,
                    'codigo_grado'    => $pe->codigo_grado,
                    'id_mencion'      => $pe->id_mencion,
                    'horas_semanales' => $pe->horas_semanales,
                    'tipo_evaluacion' => $pe->tipo_evaluacion,
                    'obligatoria'     => $pe->obligatoria,
                    'creditos'        => $pe->creditos,
                ])->values(),
            ],
            'secciones' => [
                'total' => $seccionesOrigen->count(),
                'filas' => $seccionesOrigen->map(fn ($sec) => [
                    'codigo_seccion'    => $sec->codigo_seccion,
                    'letra'             => $sec->letra,
                    'codigo_grado'      => $sec->codigo_grado,
                    'nombre_grado'      => $sec->grado->nombre ?? $sec->codigo_grado,
                    'id_mencion'        => $sec->id_mencion,
                    'nombre_mencion'    => $sec->mencion->nombre ?? null,
                    'capacidad_maxima'  => $sec->capacidad_maxima,
                    'turno'             => $sec->getRawOriginal('turno'),
                    'aula_asignada'     => $sec->aula_asignada,
                    'docente_guia'      => $sec->docenteGuia?->personal
                        ? ($sec->docenteGuia->personal->nombres . ' ' . $sec->docenteGuia->personal->apellidos)
                        : null,
                    'nuevo_codigo'      => $this->generarCodigoSeccion($sec, $destino),
                ])->values(),
            ],
            'asignaciones' => [
                'total' => $asigOrigen->count(),
                'filas' => $asigOrigen->map(fn ($asig) => [
                    'siglas_materia'  => $asig->siglas_materia,
                    'nombre_materia'  => $asig->materia->nombre ?? $asig->siglas_materia,
                    'codigo_seccion'  => $asig->codigo_seccion,
                    'nombre_grado'    => $asig->seccion?->grado?->nombre ?? $asig->codigo_grado,
                    'letra_seccion'   => $asig->seccion?->letra ?? '?',
                    'cedula_docente'  => $asig->cedula_docente,
                    'nombre_docente'  => $asig->docente?->personal
                        ? ($asig->docente->personal->nombres . ' ' . $asig->docente->personal->apellidos)
                        : 'Sin asignar',
                    'horas_asignadas' => $asig->horas_asignadas,
                ])->values(),
            ],
            'destino_existente' => [
                'plan_estudios' => $planDestino,
                'secciones'     => $seccionesDestino,
                'asignaciones'  => $asigDestino,
            ],
        ]);
    }

    /**
     * Ejecutar la copia de configuración del año origen al año destino.
     * Cada entidad se copia solo si su checkbox correspondiente está marcado.
     * Usa firstOrCreate para idempotencia (no duplica si se ejecuta dos veces).
     *
     * POST /api/anios-escolares/copiar-config
     */
    public function copiarConfiguracion(Request $request): JsonResponse
    {
        $request->validate([
            'codigo_ano_origen'   => 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
            'codigo_ano_destino'  => 'required|string|exists:Anio_Escolar,codigo_ano_escolar',
            'copiar_plan'         => 'required|boolean',
            'copiar_secciones'    => 'required|boolean',
            'copiar_asignaciones' => 'required|boolean',
        ]);

        $origen  = $request->codigo_ano_origen;
        $destino = $request->codigo_ano_destino;

        if ($origen === $destino) {
            return response()->json(['error' => 'El año origen y destino no pueden ser iguales.'], 422);
        }

        $resultados = [
            'plan_copiados'         => 0,
            'secciones_copiadas'    => 0,
            'asignaciones_copiadas' => 0,
            'errores'               => [],
        ];

        DB::transaction(function () use ($request, $origen, $destino, &$resultados) {

            // ── 1. Copiar Plan de Estudios ──────────────────────
            if ($request->copiar_plan) {
                $planOrigen = PlanEstudios::where('codigo_ano_escolar', $origen)->get();

                foreach ($planOrigen as $pe) {
                    try {
                        $created = PlanEstudios::firstOrCreate(
                            [
                                'siglas_materia'     => $pe->siglas_materia,
                                'id_mencion'         => $pe->id_mencion,
                                'codigo_grado'       => $pe->codigo_grado,
                                'codigo_ano_escolar' => $destino,
                            ],
                            [
                                'horas_semanales' => $pe->horas_semanales,
                                'obligatoria'     => $pe->obligatoria,
                                'tipo_evaluacion' => $pe->tipo_evaluacion,
                                'se_repara'       => $pe->se_repara,
                                'creditos'        => $pe->creditos,
                                'estado'          => $pe->estado,
                            ]
                        );
                        if ($created->wasRecentlyCreated) {
                            $resultados['plan_copiados']++;
                        }
                    } catch (\Throwable $e) {
                        $resultados['errores'][] = "Plan {$pe->siglas_materia}: " . $e->getMessage();
                    }
                }
            }

            // ── 2. Copiar Secciones ─────────────────────────────
            if ($request->copiar_secciones) {
                $seccionesOrigen = Seccion::where('codigo_ano_escolar', $origen)
                    ->orderBy('codigo_grado')->orderBy('letra')->get();

                foreach ($seccionesOrigen as $sec) {
                    try {
                        $nuevoCodigo = $this->generarCodigoSeccion($sec, $destino);

                        $created = Seccion::firstOrCreate(
                            ['codigo_seccion' => $nuevoCodigo],
                            [
                                'letra'               => $sec->letra,
                                'codigo_grado'        => $sec->codigo_grado,
                                'codigo_ano_escolar'  => $destino,
                                'id_mencion'          => $sec->id_mencion,
                                'cedula_docente_guia' => $sec->cedula_docente_guia,
                                'capacidad_maxima'    => $sec->capacidad_maxima,
                                'turno'               => $sec->getRawOriginal('turno'),
                                'aula_asignada'       => $sec->aula_asignada,
                            ]
                        );
                        if ($created->wasRecentlyCreated) {
                            $resultados['secciones_copiadas']++;
                        }
                    } catch (\Throwable $e) {
                        $resultados['errores'][] = "Sección {$sec->codigo_seccion}: " . $e->getMessage();
                    }
                }
            }

            // ── 3. Copiar Asignaciones Docentes (sugerencia editable) ──
            if ($request->copiar_asignaciones) {
                $asigOrigen = AsignacionDocente::with('seccion')
                    ->where('codigo_ano_escolar', $origen)->get();

                foreach ($asigOrigen as $asig) {
                    try {
                        // Buscar la sección equivalente en el destino (mismo grado + letra)
                        $seccionOrigen  = $asig->seccion;
                        if (!$seccionOrigen) continue;

                        $seccionDestino = Seccion::where('codigo_ano_escolar', $destino)
                            ->where('codigo_grado', $seccionOrigen->codigo_grado)
                            ->where('letra', $seccionOrigen->letra)
                            ->first();

                        if (!$seccionDestino) {
                            $resultados['errores'][] = "Asignación {$asig->siglas_materia}: no se encontró sección equivalente en destino ({$seccionOrigen->codigo_grado}{$seccionOrigen->letra}).";
                            continue;
                        }

                        $created = AsignacionDocente::firstOrCreate(
                            [
                                'codigo_seccion'     => $seccionDestino->codigo_seccion,
                                'siglas_materia'     => $asig->siglas_materia,
                                'codigo_ano_escolar' => $destino,
                            ],
                            [
                                'cedula_docente'  => $asig->cedula_docente,
                                'id_mencion'      => $asig->id_mencion,
                                'codigo_grado'    => $asig->codigo_grado,
                                'horas_asignadas' => $asig->horas_asignadas,
                            ]
                        );
                        if ($created->wasRecentlyCreated) {
                            $resultados['asignaciones_copiadas']++;
                        }
                    } catch (\Throwable $e) {
                        $resultados['errores'][] = "Asignación {$asig->siglas_materia}: " . $e->getMessage();
                    }
                }
            }
        });

        // Auditoría de la operación
        $detalleAuditoria = [
            'origen'                 => $origen,
            'destino'                => $destino,
            'copiar_plan'            => $request->copiar_plan,
            'copiar_secciones'       => $request->copiar_secciones,
            'copiar_asignaciones'    => $request->copiar_asignaciones,
            'plan_copiados'          => $resultados['plan_copiados'],
            'secciones_copiadas'     => $resultados['secciones_copiadas'],
            'asignaciones_copiadas'  => $resultados['asignaciones_copiadas'],
        ];

        self::registrarAuditoria(
            'Configuracion_Anual',
            "{$origen} → {$destino}",
            'I',
            null,
            $detalleAuditoria
        );

        return response()->json([
            'success'    => true,
            'message'    => 'Configuración copiada correctamente.',
            'resultados' => $resultados,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  MÉTODOS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Generar código de sección para el año destino.
     * Patrón: "{gradoNum}{letra}-{anioCorto}"  → e.g., "1A-2728"
     * Si ya existe, agrega un sufijo incremental: "1A-2728-2"
     */
    private function generarCodigoSeccion(Seccion $sec, string $anioDestino): string
    {
        // Extraer la parte numérica corta del año escolar
        // "2027-2028" → "2728", "2026-2027" → "2627"
        $partes = explode('-', $anioDestino);
        if (count($partes) >= 2) {
            $anioCorto = substr($partes[0], -2) . substr($partes[1], -2);
        } else {
            $anioCorto = substr($anioDestino, -4);
        }

        // Extraer número de grado del código (e.g., "1ER" → "1", "4TO" → "4")
        $gradoNum = preg_replace('/[^0-9]/', '', $sec->codigo_grado);
        if (empty($gradoNum)) $gradoNum = $sec->codigo_grado;

        $base = "{$gradoNum}{$sec->letra}-{$anioCorto}";

        // Verificar si ya existe; si sí, agregar sufijo incremental
        if (!Seccion::where('codigo_seccion', $base)->exists()) {
            return $base;
        }

        $i = 2;
        while (Seccion::where('codigo_seccion', "{$base}-{$i}")->exists()) {
            $i++;
        }

        return "{$base}-{$i}";
    }

    private function sincronizarEstadoVigente(array $data, ?string $codigo = null): void
    {
        if (($data['estado'] ?? null) !== self::ESTADO_VIGENTE) {
            return;
        }

        $this->finalizarAniosVigentesExcept($codigo);
    }

    private function finalizarAniosVigentesExcept(?string $codigo): void
    {
        AnioEscolar::where('estado', self::ESTADO_VIGENTE)
            ->when($codigo !== null, fn ($query) => $query->where('codigo_ano_escolar', '!=', $codigo))
            ->update(['estado' => 'cerrado']);
    }
}
