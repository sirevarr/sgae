<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
// DomPDF removed — PDF routes deleted

use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Evaluacion;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// RUTAS PROTEGIDAS POR LOGIN
Route::middleware(['auth', 'verified'])->group(function () {
    
    // 0. Dashboard Principal 
    Route::get('/dashboard', function () {
        $estudiantesCount = Estudiante::count();
        $materiasCount = Materia::where('estado', 'activa')->count(); 
        $promedioGlobal = Evaluacion::avg('promedio') ?? 0;
        
        $totalNotas = Evaluacion::count();
        $aprobados = Evaluacion::where('estado', 'aprobado')->count();
        $eficiencia = $totalNotas > 0 ? ($aprobados / $totalNotas) * 100 : 0;

        return Inertia::render('Dashboard', [
            'stats' => [
                'estudiantesCount' => $estudiantesCount,
                'materiasCount' => $materiasCount,
                'promedioGlobal' => number_format($promedioGlobal, 2),
                'porcentajeAprobados' => round($eficiencia)
            ]
        ]);
    })->name('dashboard');

    // 1 a 4. Módulos de Gestión 
    Route::get('/estudiantes', fn() => Inertia::render('Estudiantes/Index'))->name('estudiantes.index');
    Route::get('/evaluaciones', fn() => Inertia::render('Evaluaciones/Index'))->name('evaluaciones.index');
    Route::get('/materias', fn() => Inertia::render('Materias/Index'))->name('materias.index');
    Route::get('/inscripciones', fn() => Inertia::render('Inscripciones/Index'))->name('inscripciones.index');

    // PDF report route removed

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';