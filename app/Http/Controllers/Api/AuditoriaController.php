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
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $term = '%' . trim($request->buscar) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('tabla_afectada', 'LIKE', $term)
                        ->orWhere('id_registro_afectado', 'LIKE', $term)
                        ->orWhereHas('usuario', fn ($u) =>
                            $u->where('codigo_usuario', 'LIKE', $term)
                              ->orWhereHas('personal', fn ($p) =>
                                  $p->where('nombres', 'LIKE', $term)
                                    ->orWhere('apellidos', 'LIKE', $term)
                              )
                        );
                });
            })
            ->when($request->filled('tabla_afectada'), fn ($q) =>
                $q->where('tabla_afectada', 'LIKE', '%' . trim($request->tabla_afectada) . '%')
            )
            ->when($request->filled('id_usuario'), fn ($q) =>
                $q->where('id_usuario', $request->id_usuario)
            )
            ->when($request->filled('operacion'), function ($q) use ($request) {
                $op = strtoupper(trim($request->operacion));
                // Normalizar "INSERT" -> "I", "UPDATE" -> "U", "DELETE" -> "D"
                $map = ['INSERT' => 'I', 'UPDATE' => 'U', 'DELETE' => 'D'];
                $opCode = $map[$op] ?? substr($op, 0, 1);
                $q->where('operacion', $opCode);
            })
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
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $term = '%' . trim($request->buscar) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('ip_acceso', 'LIKE', $term)
                        ->orWhereHas('usuario', fn ($u) =>
                            $u->where('codigo_usuario', 'LIKE', $term)
                              ->orWhereHas('personal', fn ($p) =>
                                  $p->where('nombres', 'LIKE', $term)
                                    ->orWhere('apellidos', 'LIKE', $term)
                              )
                        );
                });
            })
            ->when($request->filled('id_usuario'), fn ($q) =>
                $q->where('id_usuario', $request->id_usuario)
            )
            ->when($request->has('exitoso') && $request->exitoso !== '' && $request->exitoso !== null, fn ($q) =>
                $q->where('exitoso', $request->boolean('exitoso'))
            );
    }
}
