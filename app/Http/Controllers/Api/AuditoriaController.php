<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\LoginRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = $this->buildAuditoriaQuery($request)
            ->orderByDesc('fecha_hora')
            ->paginate(30);

        return response()->json($q);
    }

    public function logins(Request $request): JsonResponse
    {
        $q = $this->buildLoginQuery($request)
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->paginate(30);

        return response()->json($q);
    }

    private function buildAuditoriaQuery(Request $request)
    {
        return Auditoria::with('usuario.personal')
            ->when($request->filled('tabla_afectada'), fn ($q) =>
                $q->where('tabla_afectada', $request->tabla_afectada)
            )
            ->when($request->filled('id_usuario'), fn ($q) =>
                $q->where('id_usuario', $request->id_usuario)
            )
            ->when($request->filled('operacion'), fn ($q) =>
                $q->where('operacion', $request->operacion)
            )
            ->when($request->filled('fecha_desde'), fn ($q) =>
                $q->whereDate('fecha_hora', '>=', $request->fecha_desde)
            )
            ->when($request->filled('fecha_hasta'), fn ($q) =>
                $q->whereDate('fecha_hora', '<=', $request->fecha_hasta)
            );
    }

    private function buildLoginQuery(Request $request)
    {
        return LoginRecord::with('usuario.personal')
            ->when($request->filled('id_usuario'), fn ($q) =>
                $q->where('id_usuario', $request->id_usuario)
            )
            ->when($request->has('exitoso'), fn ($q) =>
                $q->where('exitoso', $request->boolean('exitoso'))
            );
    }
}
