<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes — SGAE
|--------------------------------------------------------------------------
| El registro libre está deshabilitado: los usuarios solo se crean desde
| el panel de administración (UsuarioController).
| El reset de contraseña por email no aplica porque Usuario no tiene email
| como identificador principal.
*/

Route::middleware('guest')->group(function () {
    // Login
    Route::get('login',  [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Registro deshabilitado — redirigir a login
    Route::get('register', fn() => redirect()->route('login'))->name('register');

    // Password reset deshabilitado — no aplica con tabla Usuario
    Route::get('forgot-password', fn() => redirect()->route('login'))->name('password.request');
    Route::get('reset-password/{token}', fn() => redirect()->route('login'))->name('password.reset');
});

Route::middleware('auth')->group(function () {
    // Cambio de contraseña desde perfil (usa clave_hash)
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Confirmación de contraseña
    Route::get('confirm-password',  [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Logout
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
