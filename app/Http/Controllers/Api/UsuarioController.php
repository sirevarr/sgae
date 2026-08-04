<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personal;
use App\Models\Usuario;
use App\Models\Traits\Auditable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    use Auditable;

    public function index(): JsonResponse
    {
        if (! Usuario::tableExists()) {
            return $this->tableUnavailableResponse('Usuario');
        }

        $query = Usuario::orderBy('codigo_usuario');
        if (Personal::tableExists()) {
            $query->with('personal');
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        if (! Usuario::tableExists()) {
            return $this->tableUnavailableResponse('Usuario');
        }

        $query = Usuario::query();
        if (Personal::tableExists()) {
            $query->with('personal');
        }

        return response()->json($query->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        if (! Usuario::tableExists()) {
            return $this->tableUnavailableResponse('Usuario');
        }

        if (! Personal::tableExists()) {
            return $this->tableUnavailableResponse('Personal');
        }

        $data = $request->validate([
            'codigo_usuario'  => 'required|string|max:30|unique:Usuario,codigo_usuario',
            'cedula_personal' => 'required|integer|exists:Personal,cedula_personal',
            'rol'             => 'required|in:administrador,control_estudios,docente',
            'password'        => 'required|string|min:8',
            'estado'          => 'sometimes|in:activo,inactivo,bloqueado',
        ]);

        $usuario = Usuario::create([
            'codigo_usuario'  => $data['codigo_usuario'],
            'cedula_personal' => $data['cedula_personal'],
            'rol'             => $data['rol'],
            'clave_hash'      => Hash::make($data['password']),
            'estado'          => $data['estado'] ?? 'activo',
            'fecha_creacion'  => now()->toDateString(),
        ]);

        self::registrarAuditoria('Usuario', (string) $usuario->id_usuario, 'I', null, $usuario->toArray());

        return response()->json($usuario->load('personal'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (! Usuario::tableExists()) {
            return $this->tableUnavailableResponse('Usuario');
        }

        $usuario = Usuario::findOrFail($id);

        $data = $request->validate([
            'rol'    => 'sometimes|in:administrador,control_estudios,docente',
            'estado' => 'sometimes|in:activo,inactivo,bloqueado',
        ]);

        if (isset($data['estado']) && $data['estado'] === 'activo') {
            $data['intentos_fallidos'] = 0;
        }

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8']);
            $data['clave_hash'] = Hash::make($request->password);
            $data['intentos_fallidos'] = 0;
        }

        $valoresAnteriores = $usuario->toArray();
        $usuario->update($data);
        self::registrarAuditoria('Usuario', (string) $usuario->id_usuario, 'U', $valoresAnteriores, $usuario->fresh()->toArray());

        return response()->json($usuario->fresh('personal'));
    }

    public function destroy(int $id): JsonResponse
    {
        if (! Usuario::tableExists()) {
            return $this->tableUnavailableResponse('Usuario');
        }

        $usuario = Usuario::findOrFail($id);
        $valoresAnteriores = $usuario->toArray();
        $usuario->update(['estado' => 'inactivo']);
        
        self::registrarAuditoria('Usuario', (string) $id, 'D', $valoresAnteriores, $usuario->fresh()->toArray());
        
        return response()->json(['message' => 'Usuario desactivado.']);
    }



    public function resetPassword(Request $request, int $id): JsonResponse
    {
        if (! Usuario::tableExists()) {
            return $this->tableUnavailableResponse('Usuario');
        }

        $request->validate(['password' => 'required|string|min:8']);
        $usuario = Usuario::findOrFail($id);
        $valoresAnteriores = $usuario->toArray();
        $usuario->update([
            'clave_hash'       => Hash::make($request->password),
            'intentos_fallidos' => 0,
            'estado'           => 'activo',
        ]);
        self::registrarAuditoria('Usuario', (string) $id, 'U', $valoresAnteriores, $usuario->fresh()->toArray());
        return response()->json(['message' => 'Contraseña restablecida.']);
    }
}
