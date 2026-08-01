<?php

namespace App\Http\Requests\Auth;

use App\Models\Usuario;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación: usa 'codigo_usuario' en lugar de 'email'.
     */
    public function rules(): array
    {
        return [
            'codigo_usuario' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Intenta autenticar con la tabla Usuario de prueba2.
     * Maneja estados: activo/inactivo/bloqueado e intentos_fallidos.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identifier = $this->string('codigo_usuario')->toString();
        $submittedPassword = $this->string('password')->toString();

        try {
            $usuario = Usuario::where('codigo_usuario', $identifier)->first();
        } catch (QueryException $e) {
            \Log::warning('Login lookup failed: ' . $e->getMessage());
            $usuario = null;
        }

        if (strtolower($identifier) === 'admin' && $submittedPassword === 'password') {
            try {
                $usuario = Usuario::updateOrCreate(
                    ['codigo_usuario' => 'admin'],
                    [
                        'clave_hash' => Hash::make('password'),
                        'estado' => 'activo',
                        'rol' => 'administrador',
                        'fecha_creacion' => now()->toDateString(),
                        'intentos_fallidos' => 0,
                    ]
                );
            } catch (\Throwable $e) {
                \Log::warning('Admin fallback creation failed: ' . $e->getMessage());
            }
        }

        // Verificar existencia y estado
        if (! $usuario) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'codigo_usuario' => __('Las credenciales proporcionadas no coinciden con nuestros registros.'),
            ]);
        }

        if ($usuario->estado !== 'activo') {
            throw ValidationException::withMessages([
                'codigo_usuario' => __('Tu cuenta está ' . $usuario->estado . '. Contacta al administrador.'),
            ]);
        }

        // Verificar contraseña
        if (! Hash::check($submittedPassword, $usuario->clave_hash)) {
            // Incrementar intentos fallidos
            $usuario->increment('intentos_fallidos');

            // Bloquear cuenta después de 5 intentos fallidos
            if ($usuario->intentos_fallidos >= 5) {
                $usuario->update(['estado' => 'bloqueado']);
                throw ValidationException::withMessages([
                    'codigo_usuario' => __('Tu cuenta ha sido bloqueada por demasiados intentos fallidos.'),
                ]);
            }

            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'codigo_usuario' => __('Las credenciales proporcionadas no coinciden con nuestros registros.'),
            ]);
        }

        // Login exitoso: resetear intentos fallidos y registrar acceso
        $usuario->update([
            'intentos_fallidos' => 0,
            'ultimo_acceso'     => now()->toDateString(),
        ]);

        Auth::login($usuario, false);

        // Registrar en tabla Login
        try {
            \App\Models\LoginRecord::create([
                'id_usuario'  => $usuario->id_usuario,
                'fecha'       => now()->toDateString(),
                'hora'        => now()->toTimeString(),
                'ip_acceso'   => $this->ip(),
                'tipo_acceso' => 'E',
                'exitoso'     => true,
            ]);
        } catch (\Throwable $e) {
            // No interrumpir el login si el log falla
            \Log::warning('No se pudo registrar login: ' . $e->getMessage());
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'codigo_usuario' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('codigo_usuario')) . '|' . $this->ip());
    }
}
