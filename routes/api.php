<?php

use Illuminate\Support\Facades\Route;
// Importamos los controladores que creamos antes
use App\Http\Controllers\Api\EstudianteController;
use App\Http\Controllers\Api\MateriaController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\EvaluacionController;

// Módulo de Estudiantes
Route::apiResource('estudiantes', EstudianteController::class);

// Módulo de Materias
Route::apiResource('materias', MateriaController::class);

// Módulo de Inscripciones y Carga Académica
Route::apiResource('inscripciones', InscripcionController::class);
Route::get('inscripciones/carga-academica/{estudiante}/{periodo}', [InscripcionController::class, 'cargaAcademica']);

// Módulo de Evaluaciones y Reportes
Route::apiResource('evaluaciones', EvaluacionController::class);
Route::get('evaluaciones/reporte/{estudiante}', [EvaluacionController::class, 'reporteAcademico']);