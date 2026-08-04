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
    RespaldoController,
};

/*
|--------------------------------------------------------------------------
| API Routes — SGAE (prueba2)
|--------------------------------------------------------------------------
| Estas rutas aceptan autenticación de sesión web y validación de roles.
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

    // ── DASHBOARD & LISTAS DE CONSULTA ─────────────────────────────────
    Route::get('/dashboard-stats', [DashboardController::class, 'stats']);
    Route::get('/personal-lista',   [InstitucionController::class, 'personalLista']);

    // ── ROL: ADMINISTRADOR ──────────────────────────────────────────────
    Route::middleware(['role:administrador'])->group(function () use ($usuarioRoute) {
        Route::get('/usuarios',                       [UsuarioController::class, 'index']);
        Route::get($usuarioRoute,                     [UsuarioController::class, 'show']);
        Route::post('/usuarios',                      [UsuarioController::class, 'store']);
        Route::put($usuarioRoute,                     [UsuarioController::class, 'update']);
        Route::delete($usuarioRoute,                  [UsuarioController::class, 'destroy']);
        Route::post($usuarioRoute . '/reset-password', [UsuarioController::class, 'resetPassword']);

        Route::get('/auditoria',                      [AuditoriaController::class, 'index']);
        Route::get('/auditoria/logins',               [AuditoriaController::class, 'logins']);

        // Punto 5 — Respaldo de base de datos (solo administrador)
        Route::post('/respaldos',                     [RespaldoController::class, 'generar']);
        Route::get('/respaldos',                      [RespaldoController::class, 'index']);
        Route::get('/respaldos/{archivo}',            [RespaldoController::class, 'descargar']);
    });

    // ── ROL: ADMINISTRADOR Y CONTROL DE ESTUDIOS ────────────────────────
    Route::middleware(['role:administrador,control_estudios'])->group(function () use ($personalRoute, $materiaRoute, $planEstudiosRoute) {
        Route::post('/institucion',                   [InstitucionController::class, 'store']);
        Route::put('/institucion/{codigo}',           [InstitucionController::class, 'update']);
        Route::delete('/institucion/{codigo}',        [InstitucionController::class, 'destroy']);

        Route::post('/personal',                      [PersonalController::class, 'store']);
        Route::put($personalRoute,                    [PersonalController::class, 'update']);
        Route::delete($personalRoute,                 [PersonalController::class, 'destroy']);

        Route::post('/anios-escolares',               [AnioEscolarController::class, 'store']);
        Route::put('/anios-escolares/{codigo}',       [AnioEscolarController::class, 'update']);
        Route::delete('/anios-escolares/{codigo}',    [AnioEscolarController::class, 'destroy']);

        // Punto 6 — Copiar configuración del año anterior
        Route::get('/anios-escolares/copiar-config/preview',  [AnioEscolarController::class, 'previsualizarCopia']);
        Route::post('/anios-escolares/copiar-config',         [AnioEscolarController::class, 'copiarConfiguracion']);

        Route::post('/grados',                        [GradoController::class, 'store']);
        Route::put('/grados/{codigo}',                [GradoController::class, 'update']);
        Route::delete('/grados/{codigo}',             [GradoController::class, 'destroy']);

        Route::post('/menciones',                     [MencionController::class, 'store']);
        Route::put('/menciones/{id}',                 [MencionController::class, 'update']);
        Route::delete('/menciones/{id}',              [MencionController::class, 'destroy']);

        Route::post('/materias',                      [MateriaController::class, 'store']);
        Route::put($materiaRoute,                     [MateriaController::class, 'update']);
        Route::delete($materiaRoute,                  [MateriaController::class, 'destroy']);

        Route::post($planEstudiosRoute,               [PlanEstudiosController::class, 'store']);
        Route::put($planEstudiosRoute,                [PlanEstudiosController::class, 'update']);
        Route::delete($planEstudiosRoute,             [PlanEstudiosController::class, 'destroy']);
    });

    // ── ROL: TODOS LOS ROLES (ADMIN, CONTROL ESTUDIOS, DOCENTE) ─────────
    Route::middleware(['role:administrador,control_estudios,docente'])->group(function () use ($personalRoute, $materiaRoute, $planEstudiosRoute, $seccionRoute, $momentoRoute) {
        Route::get('/institucion',                    [InstitucionController::class, 'show']);

        Route::get('/personal',                       [PersonalController::class, 'index']);
        Route::get($personalRoute,                    [PersonalController::class, 'show']);

        Route::get('/anios-escolares/vigente',        [AnioEscolarController::class, 'vigente']);
        Route::get('/anios-escolares',                [AnioEscolarController::class, 'index']);
        Route::get('/anios-escolares/{codigo}',       [AnioEscolarController::class, 'show']);

        Route::get('/grados',                         [GradoController::class, 'index']);

        Route::get('/menciones',                      [MencionController::class, 'index']);

        Route::get('/materias',                       [MateriaController::class, 'index']);
        Route::get($materiaRoute,                     [MateriaController::class, 'show']);

        Route::get($planEstudiosRoute,                [PlanEstudiosController::class, 'index']);

        // Secciones
        Route::get('/secciones',                      [SeccionController::class, 'index']);
        Route::get($seccionRoute,                     [SeccionController::class, 'show']);

        Route::middleware(['role:administrador,control_estudios'])->group(function () use ($seccionRoute) {
            Route::post('/secciones',                     [SeccionController::class, 'store']);
            Route::put($seccionRoute,                     [SeccionController::class, 'update']);
            Route::delete($seccionRoute,                  [SeccionController::class, 'destroy']);
            Route::post($seccionRoute . '/asignaciones',  [SeccionController::class, 'asignarDocente']);
        });

        // Estudiantes
        Route::get('/estudiantes',                    [EstudianteController::class, 'index']);
        Route::get('/estudiantes/{cedula}',           [EstudianteController::class, 'show']);
        Route::middleware(['role:administrador,control_estudios'])->group(function () {
            Route::post('/estudiantes',                   [EstudianteController::class, 'store']);
            Route::put('/estudiantes/{cedula}',           [EstudianteController::class, 'update']);
            Route::delete('/estudiantes/{cedula}',        [EstudianteController::class, 'destroy']);
            Route::post('/estudiantes/{cedula}/ficha',    [EstudianteController::class, 'fichaAntropometrica']);
        });

        // Representantes
        Route::get('/representantes',                 [RepresentanteController::class, 'index']);
        Route::get('/representantes/{cedula}',        [RepresentanteController::class, 'show']);
        Route::middleware(['role:administrador,control_estudios'])->group(function () {
            Route::post('/representantes',                [RepresentanteController::class, 'store']);
            Route::put('/representantes/{cedula}',        [RepresentanteController::class, 'update']);
            Route::delete('/representantes/{cedula}',     [RepresentanteController::class, 'destroy']);
        });

        // Matrículas
        Route::get('/matriculas',                     [MatriculaController::class, 'index']);
        Route::get('/matriculas/{id}',                [MatriculaController::class, 'show']);

        Route::middleware(['role:administrador,control_estudios'])->group(function () {
            Route::post('/matriculas',                [MatriculaController::class, 'store']);
            Route::put('/matriculas/{id}',            [MatriculaController::class, 'update']);
            Route::delete('/matriculas/{id}',         [MatriculaController::class, 'destroy']);
        });

        // Momentos
        Route::get($momentoRoute,                     [MomentoEvaluativoController::class, 'index']);

        Route::middleware(['role:administrador,control_estudios'])->group(function () use ($momentoRoute) {
            Route::post($momentoRoute,                [MomentoEvaluativoController::class, 'store']);
            Route::put($momentoRoute,                 [MomentoEvaluativoController::class, 'update']);
            Route::delete($momentoRoute,              [MomentoEvaluativoController::class, 'destroy']);
        });

        // Evaluaciones
        Route::get('/evaluaciones',                   [EvaluacionController::class, 'index']);
        Route::get('/evaluaciones/resumen',           [EvaluacionController::class, 'resumenSeccion']);
        Route::middleware(['role:administrador,control_estudios,docente'])->group(function () {
            Route::post('/evaluaciones/guardar',          [EvaluacionController::class, 'guardar']);
            Route::post('/evaluaciones/guardar-lote',     [EvaluacionController::class, 'guardarLote']);
        });
        // DELETE remains restricted to admin/control_estudios
        Route::middleware(['role:administrador,control_estudios'])->group(function () {
            Route::delete('/evaluaciones',                [EvaluacionController::class, 'destroy']);
        });

        // Documentos / PDF
        Route::get('/documentos',                                      [DocumentoController::class, 'index']);
        Route::get('/documentos/boletin/{cedula}/{anio}',             [DocumentoController::class, 'boletin']);
        Route::get('/documentos/constancia-estudio/{cedula}/{anio}',  [DocumentoController::class, 'constanciaEstudio']);
        Route::get('/documentos/constancia-conducta/{cedula}/{anio}', [DocumentoController::class, 'constanciaConducta']);
        Route::get('/documentos/constancia-prosecucion/{cedula}/{anio}', [DocumentoController::class, 'constanciaProsecucion']);
        Route::get('/documentos/constancia-asistencia/{cedula}/{anio}', [DocumentoController::class, 'constanciaAsistencia']);
        Route::get('/documentos/lista-seccion/{seccion}/{anio}',      [DocumentoController::class, 'listaSeccion']);
        Route::get('/documentos/resumen-seccion/{seccion}/{anio}',    [DocumentoController::class, 'resumenSeccion']);
        Route::delete('/documentos/{id}',                              [DocumentoController::class, 'destroy']);

        // RF-07 — Resumen de revisión (materias pendientes) — admin y control estudios
        Route::middleware(['role:administrador,control_estudios'])->group(function () {
            Route::get('/documentos/resumen-revision/{cedula}/{anio}', [DocumentoController::class, 'resumenRevision']);
        });

        // Inscripciones
        Route::get('/inscripciones',                  [InscripcionController::class, 'index']);
        Route::post('/inscripciones',                 [InscripcionController::class, 'store']);
        Route::get('/inscripciones/{id}',             [InscripcionController::class, 'show']);
        Route::put('/inscripciones/{id}',             [InscripcionController::class, 'update']);
        Route::delete('/inscripciones/{id}',          [InscripcionController::class, 'destroy']);

        // Materias Pendientes
        Route::get('/materias-pendientes',            [MateriaPendienteController::class, 'index']);
        Route::get('/materias-pendientes/{id}',       [MateriaPendienteController::class, 'show']);
        Route::post('/materias-pendientes',           [MateriaPendienteController::class, 'store']);
        Route::put('/materias-pendientes/{id}',       [MateriaPendienteController::class, 'update']);
        Route::delete('/materias-pendientes/{id}',    [MateriaPendienteController::class, 'destroy']);
    });
    });
