<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
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
    InscripcionController,
    MateriaPendienteController,
};

/*
|--------------------------------------------------------------------------
| API Routes — SGAE (prueba2)
|--------------------------------------------------------------------------
| Estas rutas aceptan autenticación de sesión web para que los formularios
| de Inertia/Vue puedan guardar datos sin necesidad de tokens de Sanctum.
*/

$personalRoute = '/personal/{cedula}';
$usuarioRoute = '/usuarios/{id}';
$materiaRoute = '/materias/{siglas}';
$planEstudiosRoute = '/plan-estudios';
$seccionRoute = '/secciones/{codigo}';
$momentoRoute = '/momentos';

Route::get('/debug-sqlsrv', function () {
    $host = env('DB_HOST');
    $port = env('DB_PORT');
    $database = env('DB_DATABASE');
    $username = env('DB_USERNAME');
    $password = env('DB_PASSWORD');
    $defaultConnection = config('database.default');
    $loginTimeout = env('DB_TIMEOUT', 3);

    $tests = [
        ['label' => 'env host + port', 'server' => $host, 'port' => $port],
        ['label' => 'env host only', 'server' => $host, 'port' => null],
        ['label' => 'localhost\\SQLEXPRESS', 'server' => 'localhost\\SQLEXPRESS', 'port' => null],
        ['label' => '.\\SQLEXPRESS', 'server' => '.\\SQLEXPRESS', 'port' => null],
        ['label' => '127.0.0.1\\SQLEXPRESS', 'server' => '127.0.0.1\\SQLEXPRESS', 'port' => null],
    ];

    $testResults = [];
    foreach ($tests as $test) {
        $server = $test['server'];
        $portPart = isset($test['port']) && $test['port'] !== null && trim($test['port']) !== '' ? ',' . $test['port'] : '';
        $dsn = 'sqlsrv:Server=' . $server . $portPart . ';Database=' . $database . ';LoginTimeout=' . $loginTimeout;
        $connectionStatus = null;

        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $connectionStatus = 'connected';
            unset($pdo);
        } catch (Throwable $e) {
            $connectionStatus = $e->getMessage();
        }

        $testResults[] = [
            'label' => $test['label'],
            'dsn' => $dsn,
            'status' => $connectionStatus,
        ];
    }

    $debugTables = [
        'Usuario', 'Personal', 'Docente', 'Institucion',
        'AnioEscolar', 'Grado', 'Mencion', 'Materia',
        'PlanEstudios', 'Seccion', 'Estudiante', 'Representante',
        'Matricula', 'MomentoEvaluativo', 'Evaluacion',
        'DocumentoEmitido', 'Auditoria', 'Inscripcion',
    ];

    $tableStatus = [];
    foreach ($debugTables as $table) {
        try {
            $tableStatus[$table] = Schema::hasTable($table);
        } catch (Throwable $e) {
            $tableStatus[$table] = false;
        }
    }

    return response()->json([
        'php_binary' => PHP_BINARY,
        'php_ini' => php_ini_loaded_file(),
        'pdo_sqlsrv_loaded' => extension_loaded('pdo_sqlsrv'),
        'pdo_drivers' => PDO::getAvailableDrivers(),
        'db_connection' => $defaultConnection,
        'db_host' => $host,
        'db_port' => $port,
        'db_database' => $database,
        'db_username' => $username,
        'db_password' => $password ? '***' : null,
        'connection_tests' => $testResults,
        'table_status' => $tableStatus,
    ]);
});


Route::middleware('auth')->group(function () use ($personalRoute, $usuarioRoute, $materiaRoute, $planEstudiosRoute, $seccionRoute, $momentoRoute) {

    // ── DASHBOARD ──────────────────────────────────────────────────────
    Route::get('/dashboard-stats', [DashboardController::class, 'stats']);

    // ── INSTITUCIÓN ────────────────────────────────────────────────────
    Route::get('/institucion',            [InstitucionController::class, 'show']);
    Route::post('/institucion',           [InstitucionController::class, 'store']);
    Route::put('/institucion/{codigo}',   [InstitucionController::class, 'update']);
    Route::get('/personal-lista',         [InstitucionController::class, 'personalLista']);

    // ── PERSONAL ───────────────────────────────────────────────────────
    Route::get('/personal',              [PersonalController::class, 'index']);
    Route::get($personalRoute,     [PersonalController::class, 'show']);
    Route::post('/personal',             [PersonalController::class, 'store']);
    Route::put($personalRoute,     [PersonalController::class, 'update']);
    Route::delete($personalRoute,  [PersonalController::class, 'destroy']);

    // ── USUARIOS ───────────────────────────────────────────────────────
    Route::get('/usuarios',                       [UsuarioController::class, 'index']);
    Route::get($usuarioRoute,                  [UsuarioController::class, 'show']);
    Route::post('/usuarios',                      [UsuarioController::class, 'store']);
    Route::put($usuarioRoute,                  [UsuarioController::class, 'update']);
    Route::delete($usuarioRoute,               [UsuarioController::class, 'destroy']);
    Route::post($usuarioRoute . '/reset-password',  [UsuarioController::class, 'resetPassword']);

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
    Route::get($materiaRoute,     [MateriaController::class, 'show']);
    Route::post('/materias',             [MateriaController::class, 'store']);
    Route::put($materiaRoute,     [MateriaController::class, 'update']);
    Route::delete($materiaRoute,  [MateriaController::class, 'destroy']);

    // ── PLAN DE ESTUDIOS ───────────────────────────────────────────────
    Route::get($planEstudiosRoute,     [PlanEstudiosController::class, 'index']);
    Route::post($planEstudiosRoute,    [PlanEstudiosController::class, 'store']);
    Route::put($planEstudiosRoute,     [PlanEstudiosController::class, 'update']);
    Route::delete($planEstudiosRoute,  [PlanEstudiosController::class, 'destroy']);

    // ── SECCIONES ──────────────────────────────────────────────────────
    Route::get('/secciones',                             [SeccionController::class, 'index']);
    Route::get($seccionRoute,                    [SeccionController::class, 'show']);
    Route::post('/secciones',                            [SeccionController::class, 'store']);
    Route::put($seccionRoute,                    [SeccionController::class, 'update']);
    Route::delete($seccionRoute,                 [SeccionController::class, 'destroy']);
    Route::post($seccionRoute . '/asignaciones',      [SeccionController::class, 'asignarDocente']);

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
    Route::get($momentoRoute,     [MomentoEvaluativoController::class, 'index']);
    Route::post($momentoRoute,    [MomentoEvaluativoController::class, 'store']);
    Route::put($momentoRoute,     [MomentoEvaluativoController::class, 'update']);

    // ── EVALUACIONES ───────────────────────────────────────────────────
    Route::get('/evaluaciones',                [EvaluacionController::class, 'index']);
    Route::post('/evaluaciones/guardar',       [EvaluacionController::class, 'guardar']);
    Route::post('/evaluaciones/guardar-lote',  [EvaluacionController::class, 'guardarLote']);
    Route::get('/evaluaciones/resumen',        [EvaluacionController::class, 'resumenSeccion']);

    // ── DOCUMENTOS / PDF ───────────────────────────────────────────────
    Route::get('/documentos',                                               [DocumentoController::class, 'index']);
    Route::get('/documentos/boletin/{cedula}/{anio}',                      [DocumentoController::class, 'boletin']);
    Route::get('/documentos/constancia-estudio/{cedula}/{anio}',           [DocumentoController::class, 'constanciaEstudio']);
    Route::get('/documentos/constancia-conducta/{cedula}/{anio}',          [DocumentoController::class, 'constanciaConducta']);
    Route::get('/documentos/constancia-prosecucion/{cedula}/{anio}',       [DocumentoController::class, 'constanciaProsecucion']);
    Route::get('/documentos/constancia-asistencia/{cedula}/{anio}',        [DocumentoController::class, 'constanciaAsistencia']);
    Route::get('/documentos/lista-seccion/{seccion}/{anio}',               [DocumentoController::class, 'listaSeccion']);
    Route::get('/documentos/resumen-seccion/{seccion}/{anio}',             [DocumentoController::class, 'resumenSeccion']);
    Route::delete('/documentos/{id}', [DocumentoController::class, 'destroy']);

    // DELETE helpers for newly added destroy endpoints
    Route::delete('/anios-escolares/{codigo}', [AnioEscolarController::class, 'destroy']);
    Route::delete('/institucion/{codigo}', [InstitucionController::class, 'destroy']);
    Route::delete('/estudiantes/{cedula}', [EstudianteController::class, 'destroy']);
    Route::delete('/representantes/{cedula}', [RepresentanteController::class, 'destroy']);
    Route::delete('/matriculas/{id}', [MatriculaController::class, 'destroy']);
    Route::delete($momentoRoute, [MomentoEvaluativoController::class, 'destroy']);
    Route::delete('/evaluaciones', [EvaluacionController::class, 'destroy']);

    // ── INSCRIPCIONES ─────────────────────────────────────────────────
    Route::get('/inscripciones', [InscripcionController::class, 'index']);
    Route::post('/inscripciones', [InscripcionController::class, 'store']);
    Route::get('/inscripciones/{id}', [InscripcionController::class, 'show']);
    Route::put('/inscripciones/{id}', [InscripcionController::class, 'update']);
    Route::delete('/inscripciones/{id}', [InscripcionController::class, 'destroy']);

    // ── MATERIAS PENDIENTES ──────────────────────────────
    Route::get('/materias-pendientes',       [MateriaPendienteController::class, 'index']);
    Route::get('/materias-pendientes/{id}',  [MateriaPendienteController::class, 'show']);
    Route::post('/materias-pendientes',      [MateriaPendienteController::class, 'store']);
    Route::put('/materias-pendientes/{id}',  [MateriaPendienteController::class, 'update']);
    Route::delete('/materias-pendientes/{id}', [MateriaPendienteController::class, 'destroy']);

    // ── AUDITORÍA ──────────────────────────────────────────────────────
    Route::get('/auditoria',        [AuditoriaController::class, 'index']);
    Route::get('/auditoria/logins', [AuditoriaController::class, 'logins']);
});
