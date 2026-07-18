<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\AnioEscolar;
use App\Models\Matricula;
use App\Models\ParametroSistema;
use App\Models\Evaluacion;
use App\Models\Personal;
use App\Models\Seccion;

/*
|--------------------------------------------------------------------------
| Rutas Web — SGAE Sistema de Gestión Académica Escolar
| Base de datos: prueba2
|--------------------------------------------------------------------------
*/

// Página de bienvenida → redirigir al login si no autenticado
Route::get('/', function () {
    return redirect()->route('login');
});

// ─────────────────────────────────────────────────────────────────────
//  RUTAS PROTEGIDAS POR AUTENTICACIÓN
// ─────────────────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // ── DASHBOARD ──────────────────────────────────────────────────────
    Route::get('/dashboard', function () {
        $anioVigente = AnioEscolar::vigente();

        $estudiantesCount   = Matricula::activa()->count();
        $docentesCount      = Personal::whereHas('docente')->count();
        $seccionesCount     = $anioVigente
            ? Seccion::where('codigo_ano_escolar', $anioVigente->codigo_ano_escolar)->count()
            : 0;
        $notaMinima         = ParametroSistema::notaMinima();

        $evaluaciones = $anioVigente
            ? Evaluacion::where('codigo_ano_escolar', $anioVigente->codigo_ano_escolar)
                ->whereNotNull('nota')->get()
            : collect();

        $promedioGlobal      = $evaluaciones->count() ? round($evaluaciones->avg('nota'), 2) : 0;
        $aprobados           = $evaluaciones->where('nota', '>=', $notaMinima)->count();
        $porcentajeAprobados = $evaluaciones->count()
            ? round(($aprobados / $evaluaciones->count()) * 100, 1)
            : 0;

        return Inertia::render('Dashboard', [
            'stats' => [
                'estudiantesCount'   => $estudiantesCount,
                'docentesCount'      => $docentesCount,
                'seccionesCount'     => $seccionesCount,
                'promedioGlobal'     => $promedioGlobal,
                'porcentajeAprobados' => $porcentajeAprobados,
                'anioVigente'        => $anioVigente?->codigo_ano_escolar ?? 'Sin año vigente',
            ]
        ]);
    })->name('dashboard');

    // ── MÓDULOS INERTIA (páginas Vue) ─────────────────────────────────
    Route::get('/institucion',         fn() => Inertia::render('Institucion/Index'))->name('institucion.index');
    Route::get('/personal',            fn() => Inertia::render('Personal/Index'))->name('personal.index');
    Route::get('/anios-escolares',     fn() => Inertia::render('AnioEscolar/Index'))->name('anios.index');
    Route::get('/grados',              fn() => Inertia::render('Grado/Index'))->name('grados.index');
    Route::get('/menciones',           fn() => Inertia::render('Mencion/Index'))->name('menciones.index');
    Route::get('/materias',            fn() => Inertia::render('Materia/Index'))->name('materias.index');
    Route::get('/plan-estudios',       fn() => Inertia::render('PlanEstudios/Index'))->name('plan.index');
    Route::get('/secciones',           fn() => Inertia::render('Seccion/Index'))->name('secciones.index');
    Route::get('/estudiantes',         fn() => Inertia::render('Estudiante/Index'))->name('estudiantes.index');
    Route::get('/representantes',      fn() => Inertia::render('Representante/Index'))->name('representantes.index');
    Route::get('/matriculas',          fn() => Inertia::render('Matricula/Index'))->name('matriculas.index');
    Route::get('/momentos',            fn() => Inertia::render('MomentoEvaluativo/Index'))->name('momentos.index');
    Route::get('/evaluaciones',        fn() => Inertia::render('Evaluacion/Index'))->name('evaluaciones.index');
    Route::get('/documentos',          fn() => Inertia::render('Documentos/Index'))->name('documentos.index');
    Route::get('/auditoria',           fn() => Inertia::render('Auditoria/Index'))->name('auditoria.index');
    Route::get('/usuarios',            fn() => Inertia::render('Usuario/Index'))->name('usuarios.index');

    // ── PERFIL ─────────────────────────────────────────────────────────
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';