<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnioEscolar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnioEscolarController extends Controller
{
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
            'estado'             => 'required|in:vigente,finalizado,planificado',
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
            'estado'       => 'sometimes|in:vigente,finalizado,planificado',
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
            ->update(['estado' => 'finalizado']);
    }
}
