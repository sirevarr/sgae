<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Middleware de verificación de roles del sistema SGAE.
     * Roles válidos: 'administrador', 'control_estudios', 'docente'.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }
            return redirect()->route('login');
        }

        if ($user->isAdmin() || empty($roles) || $user->hasRole($roles)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'No tiene permisos para realizar esta acción.'], 403);
        }

        return redirect()->route('dashboard')->with('error', 'No tiene permisos suficientes para acceder a esta sección.');
    }
}
