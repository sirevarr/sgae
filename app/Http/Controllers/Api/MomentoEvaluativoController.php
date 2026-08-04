<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MomentoEvaluativo;
use App\Models\AnioEscolar;
use App\Models\Traits\Auditable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MomentoEvaluativoController extends Controller
{
    use Auditable;

    public function index(Request $request): JsonResponse
    {
        $q = MomentoEvaluativo::with('anioEscolar')
            ->when($request->filled('codigo_ano_escolar'), fn ($query) =>
                $query->where('codigo_ano_escolar', $request->codigo_ano_escolar)
            )
            ->orderBy('numero_momento')
            ->get();

        return response()->json($q);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'numero_momento'     => 'required|integer|in:1,2,3',
            'codigo_ano_escolar' => 'required|string',
            'nombre'             => 'required|string|max:100',
            'fecha_inicio'       => 'nullable|date',
            'fecha_fin'          => 'nullable|date|after_or_equal:fecha_inicio',
            'porcentaje'         => 'nullable|numeric|min:0|max:100',
            'estado'             => 'required|string|max:30',
        ]);

        $existe = MomentoEvaluativo::where([
            'numero_momento'     => $data['numero_momento'],
            'codigo_ano_escolar' => $data['codigo_ano_escolar'],
        ])->exists();

        if ($existe) {
            return response()->json(['error' => 'Este momento ya existe para el año escolar indicado.'], 422);
        }

        if ($data['estado'] === 'activo') {
            $this->desactivarMomentosActivos($data['codigo_ano_escolar']);
        }

        $momento = MomentoEvaluativo::create($data);
        $idStr = $momento->codigo_ano_escolar . '-M' . $momento->numero_momento;
        self::registrarAuditoria('Momento_Evaluativo', $idStr, 'I', null, $momento->toArray());
        
        return response()->json($momento, 201);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'numero_momento'     => 'required|integer',
            'codigo_ano_escolar' => 'required|string',
            'nombre'             => 'sometimes|string|max:100',
            'fecha_inicio'       => 'sometimes|nullable|date',
            'fecha_fin'          => 'sometimes|nullable|date',
            'porcentaje'         => 'sometimes|nullable|numeric|min:0|max:100',
            'estado'             => 'sometimes|string|max:30',
        ]);

        if (($data['estado'] ?? null) === 'activo') {
            $this->desactivarMomentosActivos($data['codigo_ano_escolar'], $data['numero_momento']);
        }

        $momento = MomentoEvaluativo::where([
            'numero_momento'     => $data['numero_momento'],
            'codigo_ano_escolar' => $data['codigo_ano_escolar'],
        ])->first();
        
        $valoresAnteriores = $momento ? $momento->toArray() : [];

        MomentoEvaluativo::where([
            'numero_momento'     => $data['numero_momento'],
            'codigo_ano_escolar' => $data['codigo_ano_escolar'],
        ])->update($request->only(['nombre', 'fecha_inicio', 'fecha_fin', 'porcentaje', 'estado']));

        if ($momento) {
            $idStr = $data['codigo_ano_escolar'] . '-M' . $data['numero_momento'];
            self::registrarAuditoria('Momento_Evaluativo', $idStr, 'U', $valoresAnteriores, $momento->fresh()->toArray());
        }

        return response()->json(['message' => 'Momento actualizado.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'numero_momento' => 'required|integer',
            'codigo_ano_escolar' => 'required|string',
        ]);

        $momento = MomentoEvaluativo::where('codigo_ano_escolar', $data['codigo_ano_escolar'])
            ->where('numero_momento', $data['numero_momento'])
            ->firstOrFail();

        $valoresAnteriores = $momento->toArray();
        try {
            $idStr = $momento->codigo_ano_escolar . '-M' . $momento->numero_momento;
            $momento->delete();
            self::registrarAuditoria('Momento_Evaluativo', $idStr, 'D', $valoresAnteriores, null);
            return response()->json(null, 204);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['error' => 'No se puede eliminar el momento evaluativo porque tiene dependencias.'], 409);
        }
    }

    private function desactivarMomentosActivos(string $codigoAnoEscolar, ?int $numeroMomento = null): void
    {
        MomentoEvaluativo::where('codigo_ano_escolar', $codigoAnoEscolar)
            ->when($numeroMomento !== null, fn ($query) => $query->where('numero_momento', '!=', $numeroMomento))
            ->where('estado', 'activo')
            ->update(['estado' => 'cerrado']);
    }
}
