<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\EstudianteController;
use App\Http\Controllers\Api\MateriaController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\EvaluacionController;

// --- Módulo de Estudiantes ---
Route::apiResource('estudiantes', EstudianteController::class);

// --- Módulo de Materias ---
Route::apiResource('materias', MateriaController::class);

// --- Módulo de Inscripciones ---
Route::get('inscripciones/form-data', [InscripcionController::class, 'getFormData']);
Route::get('inscripciones/carga-academica/{estudiante}/{periodo}', [InscripcionController::class, 'cargaAcademica']);
Route::apiResource('inscripciones', InscripcionController::class);

// --- Módulo de Evaluaciones ---
// 1. RUTAS ESPECÍFICAS PRIMERO
Route::get('evaluaciones/reporte-pdf', [EvaluacionController::class, 'reportePDF']); // <--- MOVIDA AQUÍ
Route::get('evaluaciones/reporte/{estudiante}', [EvaluacionController::class, 'reporteAcademico']);
Route::get('evaluaciones/inscripciones-alumno/{estudiante}', [EvaluacionController::class, 'inscripcionesPorEstudiante']);

// 2. RECURSO DESPUÉS (Una sola vez)
Route::apiResource('evaluaciones', EvaluacionController::class);

// --- Estadísticas del Dashboard ---
Route::get('/dashboard-stats', function() {
    return [
        'estudiantesCount' => \App\Models\Estudiante::count(),
        'materiasCount' => \App\Models\Materia::count(),
        'promedioGlobal' => number_format(\App\Models\Evaluacion::avg('promedio') ?? 0, 2),
        'porcentajeAprobados' => round((\App\Models\Evaluacion::where('estado', 'aprobado')->count() / max(\App\Models\Evaluacion::count(), 1)) * 100)
    ];
});