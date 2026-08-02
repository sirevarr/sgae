<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rutas Web — SGAE Sistema de Gestión Académica Escolar
| Base de datos: prueba2
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Registro accesible para invitados
Route::get('/register', function () {
    return Inertia::render('Auth/Register');
})->name('register');
Route::post('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);

$profileRoute = '/profile';

// ─────────────────────────────────────────────────────────────────────
//  RUTAS PROTEGIDAS POR AUTENTICACIÓN Y ROLES
// ─────────────────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () use ($profileRoute) {

    // ── DASHBOARD (Accesible para todos los usuarios autenticados) ─────
    Route::get('/dashboard', function () {
        $stats = (new \App\Http\Controllers\Api\DashboardController)->getStatsData();
        return Inertia::render('Dashboard', ['stats' => $stats]);
    })->name('dashboard');

    // ── ROL: ADMINISTRADOR ──────────────────────────────────────────────
    Route::middleware(['role:administrador'])->group(function () {
        Route::get('/institucion', fn() => Inertia::render('Institucion/Index'))->name('institucion.index');
        Route::get('/auditoria',   fn() => Inertia::render('Auditoria/Index'))->name('auditoria.index');
        Route::get('/usuarios',    fn() => Inertia::render('Usuario/Index'))->name('usuarios.index');
    });

    // ── ROL: ADMINISTRADOR Y CONTROL DE ESTUDIOS ────────────────────────
    Route::middleware(['role:administrador,control_estudios'])->group(function () {
        Route::get('/personal',        fn() => Inertia::render('Personal/Index'))->name('personal.index');
        Route::get('/anios-escolares', fn() => Inertia::render('AnioEscolar/Index'))->name('anios.index');
        Route::get('/grados',          fn() => Inertia::render('Grado/Index'))->name('grados.index');
        Route::get('/menciones',       fn() => Inertia::render('Mencion/Index'))->name('menciones.index');
        Route::get('/materias',        fn() => Inertia::render('Materia/Index'))->name('materias.index');
        Route::get('/plan-estudios',   fn() => Inertia::render('PlanEstudios/Index'))->name('plan.index');
    });

    // ── ROL: TODOS LOS ROLES (ADMIN, CONTROL ESTUDIOS, DOCENTE) ─────────
    Route::middleware(['role:administrador,control_estudios,docente'])->group(function () {
        Route::get('/estudiantes',   fn() => Inertia::render('Estudiante/Index'))->name('estudiantes.index');
        Route::get('/representantes', fn() => Inertia::render('Representante/Index'))->name('representantes.index');
        Route::get('/evaluaciones',  fn() => Inertia::render('Evaluacion/Index'))->name('evaluaciones.index');
        Route::get('/documentos',    fn() => Inertia::render('Documentos/Index'))->name('documentos.index');
        Route::get('/secciones',     fn() => Inertia::render('Seccion/Index'))->name('secciones.index');
        Route::get('/matriculas',    fn() => Inertia::render('Matricula/Index'))->name('matriculas.index');
        Route::get('/momentos',      fn() => Inertia::render('MomentoEvaluativo/Index'))->name('momentos.index');
    });



    // ── PERFIL DE USUARIO ──────────────────────────────────────────────
    Route::get($profileRoute,    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch($profileRoute,  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete($profileRoute, [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';