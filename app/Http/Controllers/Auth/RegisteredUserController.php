<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'codigo_usuario'  => 'required|string|max:30|unique:Usuario,codigo_usuario',
            'cedula_personal' => 'nullable|integer|exists:Personal,cedula_personal',
            'password'        => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = Usuario::create([
            'codigo_usuario'  => $request->codigo_usuario,
            'cedula_personal' => $request->cedula_personal ?: null,
            'rol'             => 'docente',
            'clave_hash'      => Hash::make($request->password),
            'estado'          => 'activo',
            'fecha_creacion'  => now()->toDateString(),
            'intentos_fallidos' => 0,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
