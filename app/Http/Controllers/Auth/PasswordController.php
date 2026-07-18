<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     * Compatible con la tabla Usuario de prueba2 (campo clave_hash).
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ]);

        // El campo en Usuario es clave_hash, no password
        $request->user()->update([
            'clave_hash'        => Hash::make($validated['password']),
            'intentos_fallidos' => 0,
        ]);

        return back()->with('status', 'password-updated');
    }
}
