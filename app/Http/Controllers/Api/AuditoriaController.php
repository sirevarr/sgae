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
        $q = Auditoria::with('usuario.personal')
            ->when($request->tabla_afectada, fn($q) =>
                $q->where('tabla_afectada', $request->tabla_afectada)
            )
            ->when($request->id_usuario, fn($q) =>
                $q->where('id_usuario', $request->id_usuario)
            )
            ->when($request->operacion, fn($q) =>
                $q->where('operacion', $request->operacion)
            )
            ->when($request->fecha_desde, fn($q) =>
                $q->whereDate('fecha_hora', '>=', $request->fecha_desde)
            )
            ->when($request->fecha_hasta, fn($q) =>
                $q->whereDate('fecha_hora', '<=', $request->fecha_hasta)
            )
            ->orderByDesc('fecha_hora')
            ->paginate(30);

        return response()->json($q);
    }

    public function logins(Request $request): JsonResponse
    {
        $q = LoginRecord::with('usuario.personal')
            ->when($request->id_usuario, fn($q) =>
                $q->where('id_usuario', $request->id_usuario)
            )
            ->when($request->exitoso !== null, fn($q) =>
                $q->where('exitoso', $request->boolean('exitoso'))
            )
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->paginate(30);

        return response()->json($q);
    }
}
