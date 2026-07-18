<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    DashboardController,
    InstitucionController,
    PersonalController,
    UsuarioController,
    AnioEscolarController,
    GradoController,
    MencionController,
    MateriaController,
    PlanEstudiosController,
    SeccionController,
    EstudianteController,
    RepresentanteController,
    MatriculaController,
    MomentoEvaluativoController,
    EvaluacionController,
    DocumentoController,
    AuditoriaController,
};

/*
|--------------------------------------------------------------------------
| API Routes — SGAE (prueba2)
|--------------------------------------------------------------------------
| Todas las rutas aquí están protegidas por el middleware 'auth:sanctum'.
| Para peticiones desde Inertia (sesión web) se usa 'auth' en web.php.
| Las API se consumen con fetch() desde los componentes Vue via Axios.
*/

Route::middleware('auth:sanctum')->group(function () {

    // ── DASHBOARD ──────────────────────────────────────────────────────
    Route::get('/dashboard-stats', [DashboardController::class, 'stats']);

    // ── INSTITUCIÓN ────────────────────────────────────────────────────
    Route::get('/institucion',            [InstitucionController::class, 'show']);
    Route::post('/institucion',           [InstitucionController::class, 'store']);
    Route::put('/institucion/{codigo}',   [InstitucionController::class, 'update']);
    Route::get('/personal-lista',         [InstitucionController::class, 'personalLista']);

    // ── PERSONAL ───────────────────────────────────────────────────────
    Route::get('/personal',              [PersonalController::class, 'index']);
    Route::get('/personal/{cedula}',     [PersonalController::class, 'show']);
    Route::post('/personal',             [PersonalController::class, 'store']);
    Route::put('/personal/{cedula}',     [PersonalController::class, 'update']);
    Route::delete('/personal/{cedula}',  [PersonalController::class, 'destroy']);

    // ── USUARIOS ───────────────────────────────────────────────────────
    Route::get('/usuarios',                       [UsuarioController::class, 'index']);
    Route::get('/usuarios/{id}',                  [UsuarioController::class, 'show']);
    Route::post('/usuarios',                      [UsuarioController::class, 'store']);
    Route::put('/usuarios/{id}',                  [UsuarioController::class, 'update']);
    Route::delete('/usuarios/{id}',               [UsuarioController::class, 'destroy']);
    Route::post('/usuarios/{id}/reset-password',  [UsuarioController::class, 'resetPassword']);

    // ── AÑOS ESCOLARES ─────────────────────────────────────────────────
    Route::get('/anios-escolares/vigente',    [AnioEscolarController::class, 'vigente']);
    Route::get('/anios-escolares',            [AnioEscolarController::class, 'index']);
    Route::get('/anios-escolares/{codigo}',   [AnioEscolarController::class, 'show']);
    Route::post('/anios-escolares',           [AnioEscolarController::class, 'store']);
    Route::put('/anios-escolares/{codigo}',   [AnioEscolarController::class, 'update']);

    // ── GRADOS ─────────────────────────────────────────────────────────
    Route::get('/grados',             [GradoController::class, 'index']);
    Route::post('/grados',            [GradoController::class, 'store']);
    Route::put('/grados/{codigo}',    [GradoController::class, 'update']);
    Route::delete('/grados/{codigo}', [GradoController::class, 'destroy']);

    // ── MENCIONES ──────────────────────────────────────────────────────
    Route::get('/menciones',          [MencionController::class, 'index']);
    Route::post('/menciones',         [MencionController::class, 'store']);
    Route::put('/menciones/{id}',     [MencionController::class, 'update']);
    Route::delete('/menciones/{id}',  [MencionController::class, 'destroy']);

    // ── MATERIAS ───────────────────────────────────────────────────────
    Route::get('/materias',              [MateriaController::class, 'index']);
    Route::get('/materias/{siglas}',     [MateriaController::class, 'show']);
    Route::post('/materias',             [MateriaController::class, 'store']);
    Route::put('/materias/{siglas}',     [MateriaController::class, 'update']);
    Route::delete('/materias/{siglas}',  [MateriaController::class, 'destroy']);

    // ── PLAN DE ESTUDIOS ───────────────────────────────────────────────
    Route::get('/plan-estudios',     [PlanEstudiosController::class, 'index']);
    Route::post('/plan-estudios',    [PlanEstudiosController::class, 'store']);
    Route::put('/plan-estudios',     [PlanEstudiosController::class, 'update']);
    Route::delete('/plan-estudios',  [PlanEstudiosController::class, 'destroy']);

    // ── SECCIONES ──────────────────────────────────────────────────────
    Route::get('/secciones',                             [SeccionController::class, 'index']);
    Route::get('/secciones/{codigo}',                    [SeccionController::class, 'show']);
    Route::post('/secciones',                            [SeccionController::class, 'store']);
    Route::put('/secciones/{codigo}',                    [SeccionController::class, 'update']);
    Route::delete('/secciones/{codigo}',                 [SeccionController::class, 'destroy']);
    Route::post('/secciones/{codigo}/asignaciones',      [SeccionController::class, 'asignarDocente']);

    // ── ESTUDIANTES ────────────────────────────────────────────────────
    Route::get('/estudiantes',                             [EstudianteController::class, 'index']);
    Route::get('/estudiantes/{cedula}',                    [EstudianteController::class, 'show']);
    Route::post('/estudiantes',                            [EstudianteController::class, 'store']);
    Route::put('/estudiantes/{cedula}',                    [EstudianteController::class, 'update']);
    Route::post('/estudiantes/{cedula}/ficha',             [EstudianteController::class, 'fichaAntropometrica']);

    // ── REPRESENTANTES ─────────────────────────────────────────────────
    Route::get('/representantes',             [RepresentanteController::class, 'index']);
    Route::get('/representantes/{cedula}',    [RepresentanteController::class, 'show']);
    Route::post('/representantes',            [RepresentanteController::class, 'store']);
    Route::put('/representantes/{cedula}',    [RepresentanteController::class, 'update']);

    // ── MATRÍCULAS ─────────────────────────────────────────────────────
    Route::get('/matriculas',        [MatriculaController::class, 'index']);
    Route::get('/matriculas/{id}',   [MatriculaController::class, 'show']);
    Route::post('/matriculas',       [MatriculaController::class, 'store']);
    Route::put('/matriculas/{id}',   [MatriculaController::class, 'update']);

    // ── MOMENTOS EVALUATIVOS ───────────────────────────────────────────
    Route::get('/momentos',     [MomentoEvaluativoController::class, 'index']);
    Route::post('/momentos',    [MomentoEvaluativoController::class, 'store']);
    Route::put('/momentos',     [MomentoEvaluativoController::class, 'update']);

    // ── EVALUACIONES ───────────────────────────────────────────────────
    Route::get('/evaluaciones',                [EvaluacionController::class, 'index']);
    Route::post('/evaluaciones/guardar',       [EvaluacionController::class, 'guardar']);
    Route::post('/evaluaciones/guardar-lote',  [EvaluacionController::class, 'guardarLote']);
    Route::get('/evaluaciones/resumen',        [EvaluacionController::class, 'resumenSeccion']);

    // ── DOCUMENTOS / PDF ───────────────────────────────────────────────
    Route::get('/documentos',                                        [DocumentoController::class, 'index']);
    Route::get('/documentos/boletin/{cedula}/{anio}',               [DocumentoController::class, 'boletin']);
    Route::get('/documentos/constancia-estudio/{cedula}/{anio}',    [DocumentoController::class, 'constanciaEstudio']);
    Route::get('/documentos/constancia-conducta/{cedula}/{anio}',   [DocumentoController::class, 'constanciaConducta']);
    Route::get('/documentos/lista-seccion/{seccion}/{anio}',        [DocumentoController::class, 'listaSeccion']);

    // ── AUDITORÍA ──────────────────────────────────────────────────────
    Route::get('/auditoria',        [AuditoriaController::class, 'index']);
    Route::get('/auditoria/logins', [AuditoriaController::class, 'logins']);
});