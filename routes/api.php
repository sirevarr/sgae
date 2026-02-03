<?php

use Illuminate\Support\Facades\Route;

// Importamos los controladores
use App\Http\Controllers\Api\EstudianteController;
use App\Http\Controllers\Api\MateriaController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\EvaluacionController;

// --- Módulo de Estudiantes ---
Route::apiResource('estudiantes', EstudianteController::class);


// --- Módulo de Materias ---
Route::apiResource('materias', MateriaController::class);


// --- Módulo de Inscripciones ---
// Nota: Las rutas específicas van ANTES del apiResource
Route::get('inscripciones/form-data', [InscripcionController::class, 'getFormData']);
Route::get('inscripciones/carga-academica/{estudiante}/{periodo}', [InscripcionController::class, 'cargaAcademica']);
Route::apiResource('inscripciones', InscripcionController::class);


// --- Módulo de Evaluaciones y Reportes ---
// Nota: Las rutas específicas van ANTES del apiResource
Route::get('evaluaciones/reporte/{estudiante}', [EvaluacionController::class, 'reporteAcademico']);
Route::apiResource('evaluaciones', EvaluacionController::class);