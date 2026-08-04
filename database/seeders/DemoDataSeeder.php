<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Models\ParametroSistema;
use App\Models\Institucion;
use App\Models\AnioEscolar;
use App\Models\Grado;
use App\Models\Mencion;
use App\Models\Materia;
use App\Models\Personal;
use App\Models\Docente;
use App\Models\Usuario;
use App\Models\MomentoEvaluativo;
use App\Models\Seccion;
use App\Models\PlanEstudios;
use App\Models\AsignacionDocente;
use App\Models\Estudiante;
use App\Models\Representante;
use App\Models\Matricula;
use App\Models\FichaAntropometrica;
use App\Models\Evaluacion;
use App\Models\MateriaPendiente;
use App\Models\DocumentoEmitido;
use App\Models\LoginRecord;
use App\Models\Auditoria;

/**
 * DemoDataSeeder v2 — datos ricos y variados para el video de defensa.
 * ─────────────────────────────────────────────────────────────────────────
 * Sigue estrictamente el orden jerárquico de dependencias:
 * Administración → Estructura Académica → Secciones → Estudiantes → Control Académico.
 *
 * 3 menciones: Ciencias, Contabilidad, Tecnología
 * 4 secciones (una con cupo casi lleno)
 * 6 docentes (uno inactivo)
 * 13 estudiantes (cédulas V y E, condición médica, uno retirado)
 * Evaluaciones con 4 casos clave + materia literal (Educación Física)
 * Materias pendientes en 2 estados (pendiente / aprobada)
 * Documentos emitidos con folios distintos
 * Historial de accesos con intento fallido
 *
 * Ejecutar: php artisan db:seed --class=Database\\Seeders\\DemoDataSeeder
 * ─────────────────────────────────────────────────────────────────────────
 */
class DemoDataSeeder extends Seeder
{
    private const ANIO_ANTERIOR = '2024-2025';
    private const ANIO_VIGENTE  = '2025-2026';

    public function run(): void
    {
        $this->limpiarTablas();

        DB::transaction(function () {
            $this->parametros();
            $this->grados();
            [$director, $subdirectora, $coordinadora, $docentes] = $this->personal();
            $this->institucion($director, $subdirectora);
            $this->aniosEscolares();
            $this->momentosEvaluativos();
            [$ciencias, $contabilidad, $tecnologia] = $this->menciones();
            $this->materias();
            $usuarios = $this->usuarios($director, $coordinadora, $docentes);
            $secciones = $this->secciones($ciencias, $contabilidad, $tecnologia, $docentes);
            $this->planEstudios($ciencias, $contabilidad, $tecnologia);
            $this->asignacionesDocentes($docentes, $secciones, $ciencias, $contabilidad, $tecnologia);
            [$estudiantes, $representantes] = $this->estudiantesYRepresentantes();
            $this->matriculas($estudiantes, $secciones);
            $this->fichasAntropometricas($estudiantes);
            $this->evaluaciones($ciencias, $contabilidad, $tecnologia);
            $this->materiasPendientes($ciencias);
            $this->documentosEmitidos($usuarios);
            $this->loginsYAuditoria($usuarios);
        });

        if ($this->command) {
            $this->command->info('✅ DemoDataSeeder v2: datos ricos cargados en las 22 tablas.');
        }
    }

    /**
     * Limpia todos los registros previos respetando las restricciones de integridad.
     * Elimina las menciones que NO sean Ciencias, Contabilidad o Tecnología.
     */
    private function limpiarTablas(): void
    {
        // Deshabilitar restricciones FK temporalmente (SQL Server)
        try {
            DB::statement('EXEC sp_MSforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT ALL"');
        } catch (\Throwable $e) {}

        Auditoria::query()->delete();
        LoginRecord::query()->delete();
        DocumentoEmitido::query()->delete();
        Evaluacion::query()->delete();
        FichaAntropometrica::query()->delete();
        MateriaPendiente::query()->delete();
        Matricula::query()->delete();
        AsignacionDocente::query()->delete();
        PlanEstudios::query()->delete();
        Seccion::query()->delete();
        MomentoEvaluativo::query()->delete();
        Estudiante::query()->delete();
        Representante::query()->delete();
        Usuario::query()->delete();
        Docente::query()->delete();
        Personal::query()->delete();
        Materia::query()->delete();
        // Eliminar solo las menciones que NO son las tres permitidas
        Mencion::whereNotIn('nombre', ['Ciencias', 'Contabilidad', 'Tecnología'])->delete();
        Grado::query()->delete();
        Institucion::query()->delete();
        AnioEscolar::query()->delete();
        ParametroSistema::query()->delete();

        // Rehabilitar restricciones FK
        try {
            DB::statement('EXEC sp_MSforeachtable "ALTER TABLE ? CHECK CONSTRAINT ALL"');
        } catch (\Throwable $e) {}
    }

    // ══════════════════════════════════════════════════════════════════════
    // ── 1. ADMINISTRACIÓN
    // ══════════════════════════════════════════════════════════════════════

    private function parametros(): void
    {
        foreach ([
            'nota_minima_aprobatoria' => '10',
            'nombre_sistema'          => 'SGAE',
            'version'                 => '2.0.0',
            'max_estudiantes_seccion' => '35',
        ] as $clave => $valor) {
            ParametroSistema::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        }
    }

    private function grados(): void
    {
        $grados = [
            ['codigo_grado' => '1ER', 'nombre' => '1er Año', 'nivel_educativo' => 'Educación Media General', 'numero_ano' => 1, 'estado' => 'activo'],
            ['codigo_grado' => '2DO', 'nombre' => '2do Año', 'nivel_educativo' => 'Educación Media General', 'numero_ano' => 2, 'estado' => 'activo'],
            ['codigo_grado' => '3ER', 'nombre' => '3er Año', 'nivel_educativo' => 'Educación Media General', 'numero_ano' => 3, 'estado' => 'activo'],
            ['codigo_grado' => '4TO', 'nombre' => '4to Año', 'nivel_educativo' => 'Educación Media General', 'numero_ano' => 4, 'estado' => 'activo'],
            ['codigo_grado' => '5TO', 'nombre' => '5to Año', 'nivel_educativo' => 'Educación Media General', 'numero_ano' => 5, 'estado' => 'activo'],
        ];
        foreach ($grados as $g) {
            Grado::updateOrCreate(['codigo_grado' => $g['codigo_grado']], $g);
        }
    }

    private function personal(): array
    {
        $director = Personal::updateOrCreate(['cedula_personal' => 12345678], [
            'nombres' => 'María Alejandra', 'apellidos' => 'González Rivas', 'cargo' => 'Directora',
            'telefono' => '0424-1112233', 'correo' => 'direccion@nelsonmandela.edu.ve',
            'fecha_nacimiento' => '1975-03-14', 'genero' => 'F', 'fecha_ingreso' => '2010-09-01', 'estado' => 'activo',
        ]);
        $subdirectora = Personal::updateOrCreate(['cedula_personal' => 13456789], [
            'nombres' => 'Carmen Rosa', 'apellidos' => 'Delgado Pérez', 'cargo' => 'Subdirectora',
            'telefono' => '0414-2223344', 'correo' => 'subdireccion@nelsonmandela.edu.ve',
            'fecha_nacimiento' => '1980-06-22', 'genero' => 'F', 'fecha_ingreso' => '2012-01-15', 'estado' => 'activo',
        ]);
        $coordinadora = Personal::updateOrCreate(['cedula_personal' => 14567890], [
            'nombres' => 'Yusmary del Valle', 'apellidos' => 'Martínez Silva', 'cargo' => 'Coordinadora de Control de Estudios',
            'telefono' => '0426-3334455', 'correo' => 'controldeestudios@nelsonmandela.edu.ve',
            'fecha_nacimiento' => '1985-11-02', 'genero' => 'F', 'fecha_ingreso' => '2015-09-01', 'estado' => 'activo',
        ]);

        // 6 docentes: 5 activos + 1 inactivo (Beatriz Rondón)
        $docentesData = [
            [15111111, 'Luis Alberto',  'Ramírez Torres',  'Matemática',              'M', 'activo',   'M'],
            [15222222, 'Ana Karina',    'Suárez Blanco',   'Castellano y Literatura', 'F', 'activo',   'M'],
            [15333333, 'Pedro José',    'Hernández Luna',  'Biología',                'M', 'activo',   'M'],
            [15444444, 'Rosa Elena',    'Camacho Díaz',    'Inglés',                  'F', 'activo',   'T'],
            [15555555, 'Miguel Ángel',  'Ortega Peña',     'Física y Química',        'M', 'activo',   'M'],
            [15666666, 'Beatriz',       'Rondón Aguilar',  'Historia y Geografía',    'F', 'inactivo', 'T'],
        ];
        $docentes = [];
        foreach ($docentesData as [$ced, $nom, $ape, $esp, $gen, $estadoPersonal, $turno]) {
            Personal::updateOrCreate(['cedula_personal' => $ced], [
                'nombres' => $nom, 'apellidos' => $ape, 'cargo' => 'Docente',
                'telefono' => '0412-0000000',
                'correo' => strtolower(explode(' ', $nom)[0] . '.' . explode(' ', $ape)[0]) . '@nelsonmandela.edu.ve',
                'fecha_nacimiento' => '1988-01-01', 'genero' => $gen,
                'fecha_ingreso' => '2018-09-01', 'estado' => $estadoPersonal,
            ]);
            $docentes[$ced] = Docente::updateOrCreate(
                ['cedula_personal' => $ced],
                ['especialidad' => $esp, 'turno' => $turno]
            );
        }

        return [$director, $subdirectora, $coordinadora, $docentes];
    }

    private function institucion($director, $subdirectora): void
    {
        Institucion::updateOrCreate(['codigo_dea' => 'DEA-13-1234'], [
            'nombre' => 'U.E.E. Nelson Mandela',
            'direccion' => 'Av. Principal de Charallave, Sector Centro, Municipio Tomás Lander',
            'telefono' => '0239-2461234', 'municipio' => 'Tomás Lander', 'estado' => 'Miranda',
            'zona_educativa' => 'Zona Educativa Miranda',
            'director_actual' => $director->cedula_personal,
            'coordinador_academico' => $subdirectora->cedula_personal,
        ]);
    }

    private function usuarios($director, $coordinadora, $docentes): array
    {
        $u = [];
        $u['admin'] = Usuario::updateOrCreate(['codigo_usuario' => 'admin'], [
            'cedula_personal' => $director->cedula_personal, 'rol' => 'administrador',
            'clave_hash' => Hash::make('password'), 'estado' => 'activo',
            'fecha_creacion' => now()->format('Y-m-d'), 'intentos_fallidos' => 0,
        ]);
        $u['control'] = Usuario::updateOrCreate(['codigo_usuario' => 'controlestudios'], [
            'cedula_personal' => $coordinadora->cedula_personal, 'rol' => 'control_estudios',
            'clave_hash' => Hash::make('password'), 'estado' => 'activo',
            'fecha_creacion' => now()->format('Y-m-d'), 'intentos_fallidos' => 0,
        ]);
        $u['docente1'] = Usuario::updateOrCreate(['codigo_usuario' => 'lramirez'], [
            'cedula_personal' => 15111111, 'rol' => 'docente',
            'clave_hash' => Hash::make('password'), 'estado' => 'activo',
            'fecha_creacion' => now()->format('Y-m-d'), 'intentos_fallidos' => 0,
        ]);
        $u['docente2'] = Usuario::updateOrCreate(['codigo_usuario' => 'asuarez'], [
            'cedula_personal' => 15222222, 'rol' => 'docente',
            'clave_hash' => Hash::make('password'), 'estado' => 'activo',
            'fecha_creacion' => now()->format('Y-m-d'), 'intentos_fallidos' => 0,
        ]);
        // Docente bloqueado (5 intentos fallidos — demuestra la lógica de bloqueo)
        Usuario::updateOrCreate(['codigo_usuario' => 'docente_bloqueado'], [
            'cedula_personal' => 15333333, 'rol' => 'docente',
            'clave_hash' => Hash::make('password'), 'estado' => 'bloqueado',
            'fecha_creacion' => now()->format('Y-m-d'), 'intentos_fallidos' => 5,
        ]);

        return $u;
    }

    // ══════════════════════════════════════════════════════════════════════
    // ── 2. ESTRUCTURA ACADÉMICA
    // ══════════════════════════════════════════════════════════════════════

    private function aniosEscolares(): void
    {
        AnioEscolar::updateOrCreate(['codigo_ano_escolar' => self::ANIO_ANTERIOR], [
            'fecha_inicio' => '2024-09-16', 'fecha_fin' => '2025-07-15', 'estado' => 'cerrado',
        ]);
        AnioEscolar::updateOrCreate(['codigo_ano_escolar' => self::ANIO_VIGENTE], [
            'fecha_inicio' => '2025-09-15', 'fecha_fin' => '2026-07-15', 'estado' => 'vigente',
        ]);
    }

    private function momentosEvaluativos(): void
    {
        // Año vigente: M1 cerrado, M2 cerrado, M3 activo
        $momentos = [
            [1, 'Primer Momento',  '2025-09-15', '2025-11-30', 33.33, 'cerrado'],
            [2, 'Segundo Momento', '2025-12-01', '2026-03-15', 33.33, 'cerrado'],
            [3, 'Tercer Momento',  '2026-03-16', '2026-07-15', 33.34, 'activo'],
        ];
        foreach ($momentos as [$n, $nombre, $ini, $fin, $pct, $estado]) {
            MomentoEvaluativo::updateOrCreate(
                ['numero_momento' => $n, 'codigo_ano_escolar' => self::ANIO_VIGENTE],
                ['nombre' => $nombre, 'fecha_inicio' => $ini, 'fecha_fin' => $fin, 'porcentaje' => $pct, 'estado' => $estado]
            );
        }
        // Año anterior: solo M3 cerrado (para materia pendiente de 4TO)
        MomentoEvaluativo::updateOrCreate(
            ['numero_momento' => 3, 'codigo_ano_escolar' => self::ANIO_ANTERIOR],
            ['nombre' => 'Tercer Momento', 'fecha_inicio' => '2025-03-16', 'fecha_fin' => '2025-07-15', 'porcentaje' => 33.34, 'estado' => 'cerrado']
        );
    }

    /**
     * Solo 3 menciones: Ciencias, Contabilidad, Tecnología.
     * Las que ya existan se conservan; las que no, se crean.
     */
    private function menciones(): array
    {
        $ciencias     = Mencion::updateOrCreate(['nombre' => 'Ciencias'],     ['estado' => 'activo']);
        $contabilidad = Mencion::updateOrCreate(['nombre' => 'Contabilidad'], ['estado' => 'activo']);
        $tecnologia   = Mencion::updateOrCreate(['nombre' => 'Tecnología'],   ['estado' => 'activo']);

        return [$ciencias, $contabilidad, $tecnologia];
    }

    private function materias(): void
    {
        foreach ([
            ['MAT', 'Matemática',                     'Matemática'],
            ['CAS', 'Castellano y Literatura',         'Castellano y Literatura'],
            ['ING', 'Idiomas Extranjeros (Inglés)',    'Idiomas Extranjeros'],
            ['BIO', 'Biología',                        'Ciencias Naturales'],
            ['FIS', 'Física',                          'Ciencias Naturales'],
            ['QUI', 'Química',                         'Ciencias Naturales'],
            ['GHC', 'Geografía, Historia y Ciudadanía','Ciencias Sociales'],
            ['EDF', 'Educación Física',                'Educación Física'],
            ['ART', 'Arte y Patrimonio',               'Formación para la Soberanía Nacional'],
            ['CON', 'Contabilidad General',            'Contabilidad'],
            ['INF', 'Informática',                     'Tecnología'],
        ] as [$siglas, $nombre, $area]) {
            Materia::updateOrCreate(['siglas' => $siglas], ['nombre' => $nombre, 'area_formacion' => $area]);
        }
    }

    /**
     * Planes de estudio para las 3 menciones en distintos grados y años.
     * - 1ER año: materias comunes (sin mención específica, se usa Ciencias como base)
     * - 5TO Ciencias: MAT, CAS, ING, BIO, FIS, QUI, EDF(Literal)
     * - 5TO Contabilidad: MAT, CAS, ING, CON, GHC, ART, EDF(Literal)
     * - 5TO Tecnología: MAT, CAS, ING, INF, FIS, EDF(Literal)
     * - 4TO Ciencias (año anterior): FIS — para materia pendiente
     */
    private function planEstudios($ciencias, $contabilidad, $tecnologia): void
    {
        // ── 1ER año (materias comunes bajo mención Ciencias) ──
        foreach (['MAT', 'CAS', 'ING', 'GHC', 'EDF', 'ART'] as $siglas) {
            PlanEstudios::updateOrCreate(
                ['siglas_materia' => $siglas, 'id_mencion' => $ciencias->id_mencion, 'codigo_grado' => '1ER', 'codigo_ano_escolar' => self::ANIO_VIGENTE],
                ['horas_semanales' => 4, 'obligatoria' => true, 'tipo_evaluacion' => 'N', 'se_repara' => true, 'creditos' => 1, 'estado' => 'activo']
            );
        }

        // ── 5TO Ciencias ──
        foreach ([['MAT','N'], ['CAS','N'], ['ING','N'], ['BIO','N'], ['FIS','N'], ['QUI','N'], ['EDF','L']] as [$siglas, $tipo]) {
            PlanEstudios::updateOrCreate(
                ['siglas_materia' => $siglas, 'id_mencion' => $ciencias->id_mencion, 'codigo_grado' => '5TO', 'codigo_ano_escolar' => self::ANIO_VIGENTE],
                ['horas_semanales' => 4, 'obligatoria' => true, 'tipo_evaluacion' => $tipo, 'se_repara' => true, 'creditos' => 1, 'estado' => 'activo']
            );
        }

        // ── 5TO Contabilidad ──
        foreach ([['MAT','N'], ['CAS','N'], ['ING','N'], ['CON','N'], ['GHC','N'], ['ART','N'], ['EDF','L']] as [$siglas, $tipo]) {
            PlanEstudios::updateOrCreate(
                ['siglas_materia' => $siglas, 'id_mencion' => $contabilidad->id_mencion, 'codigo_grado' => '5TO', 'codigo_ano_escolar' => self::ANIO_VIGENTE],
                ['horas_semanales' => 4, 'obligatoria' => true, 'tipo_evaluacion' => $tipo, 'se_repara' => true, 'creditos' => 1, 'estado' => 'activo']
            );
        }

        // ── 5TO Tecnología ──
        foreach ([['MAT','N'], ['CAS','N'], ['ING','N'], ['INF','N'], ['FIS','N'], ['EDF','L']] as [$siglas, $tipo]) {
            PlanEstudios::updateOrCreate(
                ['siglas_materia' => $siglas, 'id_mencion' => $tecnologia->id_mencion, 'codigo_grado' => '5TO', 'codigo_ano_escolar' => self::ANIO_VIGENTE],
                ['horas_semanales' => 4, 'obligatoria' => true, 'tipo_evaluacion' => $tipo, 'se_repara' => true, 'creditos' => 1, 'estado' => 'activo']
            );
        }

        // ── 4TO Ciencias (año anterior) — para materia pendiente ──
        PlanEstudios::updateOrCreate(
            ['siglas_materia' => 'FIS', 'id_mencion' => $ciencias->id_mencion, 'codigo_grado' => '4TO', 'codigo_ano_escolar' => self::ANIO_ANTERIOR],
            ['horas_semanales' => 4, 'obligatoria' => true, 'tipo_evaluacion' => 'N', 'se_repara' => true, 'creditos' => 1, 'estado' => 'activo']
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // ── 3. GESTIÓN DE SECCIONES
    // ══════════════════════════════════════════════════════════════════════

    /**
     * 4 secciones:
     *  - 1A (1ER, sin mención, cap 35, turno M)
     *  - 1B (1ER, sin mención, cap 35, turno T)
     *  - 5A-Ciencias (5TO, Ciencias, cap 30, turno M)
     *  - 5A-Contabilidad (5TO, Contabilidad, cap 5 → cupo casi lleno!)
     */
    private function secciones($ciencias, $contabilidad, $tecnologia, $docentes): array
    {
        $s = [];
        $s['1A'] = Seccion::updateOrCreate(['codigo_seccion' => '1A-2025'], [
            'letra' => 'A', 'codigo_grado' => '1ER', 'codigo_ano_escolar' => self::ANIO_VIGENTE,
            'id_mencion' => null, 'cedula_docente_guia' => 15111111,
            'capacidad_maxima' => 35, 'turno' => 'M', 'aula_asignada' => 'Aula 1',
        ]);
        $s['1B'] = Seccion::updateOrCreate(['codigo_seccion' => '1B-2025'], [
            'letra' => 'B', 'codigo_grado' => '1ER', 'codigo_ano_escolar' => self::ANIO_VIGENTE,
            'id_mencion' => null, 'cedula_docente_guia' => 15222222,
            'capacidad_maxima' => 35, 'turno' => 'T', 'aula_asignada' => 'Aula 2',
        ]);
        $s['5ACiencias'] = Seccion::updateOrCreate(['codigo_seccion' => '5A-CIEN-2025'], [
            'letra' => 'A', 'codigo_grado' => '5TO', 'codigo_ano_escolar' => self::ANIO_VIGENTE,
            'id_mencion' => $ciencias->id_mencion, 'cedula_docente_guia' => 15333333,
            'capacidad_maxima' => 30, 'turno' => 'M', 'aula_asignada' => 'Aula 5',
        ]);
        // ¡Capacidad 5! Cupo casi lleno para mostrar validación de capacidad
        $s['5AContabilidad'] = Seccion::updateOrCreate(['codigo_seccion' => '5A-CONT-2025'], [
            'letra' => 'A', 'codigo_grado' => '5TO', 'codigo_ano_escolar' => self::ANIO_VIGENTE,
            'id_mencion' => $contabilidad->id_mencion, 'cedula_docente_guia' => 15444444,
            'capacidad_maxima' => 5, 'turno' => 'M', 'aula_asignada' => 'Aula 6',
        ]);
        return $s;
    }

    // ══════════════════════════════════════════════════════════════════════
    // ── 4. ASIGNACIÓN DOCENTE
    // ══════════════════════════════════════════════════════════════════════

    private function asignacionesDocentes($docentes, $secciones, $ciencias, $contabilidad, $tecnologia): void
    {
        $asign = [
            // Sección 1A y 1B (1ER, mención Ciencias como base)
            [15111111, '1A-2025', 'MAT', $ciencias, '1ER'],
            [15222222, '1A-2025', 'CAS', $ciencias, '1ER'],
            [15444444, '1A-2025', 'ING', $ciencias, '1ER'],
            [15333333, '1A-2025', 'GHC', $ciencias, '1ER'],
            [15555555, '1A-2025', 'ART', $ciencias, '1ER'],
            [15111111, '1A-2025', 'EDF', $ciencias, '1ER'],
            
            [15111111, '1B-2025', 'MAT', $ciencias, '1ER'],
            [15222222, '1B-2025', 'CAS', $ciencias, '1ER'],
            [15444444, '1B-2025', 'ING', $ciencias, '1ER'],
            [15333333, '1B-2025', 'GHC', $ciencias, '1ER'],
            [15555555, '1B-2025', 'ART', $ciencias, '1ER'],
            [15111111, '1B-2025', 'EDF', $ciencias, '1ER'],
            // Sección 5A-Ciencias
            [15333333, '5A-CIEN-2025', 'BIO', $ciencias, '5TO'],
            [15555555, '5A-CIEN-2025', 'FIS', $ciencias, '5TO'],
            [15555555, '5A-CIEN-2025', 'QUI', $ciencias, '5TO'],
            [15111111, '5A-CIEN-2025', 'MAT', $ciencias, '5TO'],
            // Sección 5A-Contabilidad
            [15222222, '5A-CONT-2025', 'CAS', $contabilidad, '5TO'],
            [15444444, '5A-CONT-2025', 'ING', $contabilidad, '5TO'],
            [15111111, '5A-CONT-2025', 'MAT', $contabilidad, '5TO'],
        ];
        foreach ($asign as [$cedDoc, $seccion, $materia, $mencion, $grado]) {
            AsignacionDocente::updateOrCreate(
                ['cedula_docente' => $cedDoc, 'codigo_seccion' => $seccion, 'siglas_materia' => $materia, 'codigo_ano_escolar' => self::ANIO_VIGENTE],
                ['id_mencion' => $mencion->id_mencion, 'codigo_grado' => $grado, 'horas_asignadas' => 4]
            );
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // ── 5. ESTUDIANTES Y REPRESENTANTES
    // ══════════════════════════════════════════════════════════════════════

    private function estudiantesYRepresentantes(): array
    {
        // 5 representantes
        $repData = [
            [20111111, 'Carlos Eduardo',  'Fernández Rojas',  'Padre',                          'Comerciante'],
            [20222222, 'Mariana',          'Torres Vielma',    'Madre',                          'Enfermera'],
            [20333333, 'José Gregorio',    'Salazar Ponte',    'Padre',                          'Docente'],
            [20444444, 'Yelitza',          'Blanco Guerra',    'Madre',                          'Abogada'],
            [20555555, 'Ramón Antonio',    'Peña Ibarra',      'Representante legal (tío)',       'Ingeniero'],
        ];
        $representantes = [];
        foreach ($repData as [$ced, $nom, $ape, $parentesco, $ocup]) {
            $representantes[$ced] = Representante::updateOrCreate(['cedula_representante' => $ced], [
                'nacionalidad' => 'Venezolana', 'nombres' => $nom, 'apellidos' => $ape,
                'parentesco' => $parentesco, 'ocupacion' => $ocup,
                'direccion' => 'Charallave, Municipio Tomás Lander', 'telefono' => '0412-5556677',
                'correo' => strtolower(explode(' ', $nom)[0]) . '@gmail.com', 'es_representante_legal' => true,
            ]);
        }

        // 12 estudiantes activos + 1 retirado = 13 total
        // Distribución:
        //   1A-2025:        5 activos + 1 retirado = 6
        //   1B-2025:        3 activos
        //   5A-CIEN-2025:   2 activos
        //   5A-CONT-2025:   2 activos (de cap. 5 → queda casi lleno)
        $estData = [
            // ── Sección 1A (1ER) ──
            ['30111111', 'V', 'Génesis Valentina',  'Fernández Torres',    'F', '2013-02-10', 20111111, '1A-2025'],
            ['30222222', 'V', 'Santiago José',       'Fernández Torres',    'M', '2013-05-18', 20111111, '1A-2025'],
            ['30333333', 'V', 'Valeria Isabel',      'Torres Vielma',       'F', '2013-08-25', 20222222, '1A-2025'],
            ['E1230001', 'E', 'Ángel Gabriel',       'Blanco Guerra',       'M', '2013-04-02', 20444444, '1A-2025'],
            ['30444444', 'V', 'Sofía Nazareth',      'Peña Ibarra',         'F', '2013-09-14', 20555555, '1A-2025'],
            // ── Sección 1B (1ER) ──
            ['30555555', 'V', 'Daniel Eduardo',      'Salazar Colmenares',  'M', '2013-01-20', 20333333, '1B-2025'],
            ['30666666', 'V', 'Isabella María',      'González Prado',      'F', '2013-07-07', 20222222, '1B-2025'],
            ['30777777', 'V', 'Jesús Alejandro',     'Torres Vielma',       'M', '2013-03-30', 20222222, '1B-2025'],
            // ── Sección 5A-Ciencias ──
            ['E1234567', 'E', 'Diego Alejandro',     'Salazar Colmenares',  'M', '2011-11-30', 20333333, '5A-CIEN-2025'],
            ['30888888', 'V', 'María Fernanda',      'Salazar Colmenares',  'F', '2011-01-05', 20333333, '5A-CIEN-2025'],
            // ── Sección 5A-Contabilidad (cap. 5, queda casi lleno) ──
            ['30999999', 'V', 'Andrea Carolina',     'Blanco Guerra',       'F', '2011-06-12', 20444444, '5A-CONT-2025'],
            ['31000000', 'V', 'Kevin Josué',         'Fernández Rojas',     'M', '2011-02-28', 20111111, '5A-CONT-2025'],
        ];
        $estudiantes = [];
        foreach ($estData as [$ced, $tipo, $nom, $ape, $gen, $nac, $repCed, $seccion]) {
            $estudiantes[$ced] = [
                'model' => Estudiante::updateOrCreate(['cedula_estudiante' => $ced], [
                    'tipo_documento' => $tipo, 'nacionalidad' => 'Venezolana', 'nombres' => $nom, 'apellidos' => $ape,
                    'genero' => $gen, 'fecha_nacimiento' => $nac, 'lugar_nacimiento' => 'Charallave',
                    'estado_nacimiento' => 'Miranda', 'municipio_nacimiento' => 'Tomás Lander',
                    'direccion' => 'Charallave, Municipio Tomás Lander', 'telefono' => null, 'correo' => null,
                    // Caso especial: condición médica en Génesis (30111111)
                    'condiciones_medicas' => $ced === '30111111' ? 'Asma leve, usa inhalador de rescate' : null,
                    'medicamentos' => $ced === '30111111' ? 'Salbutamol (según indicación médica)' : null,
                    'fecha_ingreso' => '2025-09-15', 'estado_estudiante' => 'activo',
                ]),
                'representante' => $repCed,
                'seccion' => $seccion,
            ];
        }

        // Estudiante #13: RETIRADO a mitad de año (Luis Fernando Marcano)
        $estudiantes['30123123'] = [
            'model' => Estudiante::updateOrCreate(['cedula_estudiante' => '30123123'], [
                'tipo_documento' => 'V', 'nacionalidad' => 'Venezolana',
                'nombres' => 'Luis Fernando', 'apellidos' => 'Marcano Díaz',
                'genero' => 'M', 'fecha_nacimiento' => '2013-06-01',
                'lugar_nacimiento' => 'Ocumare del Tuy',
                'estado_nacimiento' => 'Miranda', 'municipio_nacimiento' => 'Tomás Lander',
                'direccion' => 'Charallave', 'telefono' => null, 'correo' => null,
                'condiciones_medicas' => null, 'medicamentos' => null,
                'fecha_ingreso' => '2025-09-15',
                'estado_estudiante' => 'retirado',
                'fecha_retiro' => '2025-11-20',
                'motivo_retiro' => 'Traslado a otra institución por mudanza familiar',
            ]),
            'representante' => 20111111,
            'seccion' => '1A-2025',
        ];

        return [$estudiantes, $representantes];
    }

    // ══════════════════════════════════════════════════════════════════════
    // ── 6. MATRÍCULAS
    // ══════════════════════════════════════════════════════════════════════

    private function matriculas($estudiantes, $secciones): void
    {
        $numLista = [];
        foreach ($estudiantes as $ced => $data) {
            $cedStr = (string)$ced; // FORZAR STRING por culpa del auto-cast de keys de PHP
            $seccion = $data['seccion'];
            $numLista[$seccion] = ($numLista[$seccion] ?? 0) + 1;

            $estadoMatricula = $data['model']->estado_estudiante === 'retirado' ? 'retirada' : 'activa';

            Matricula::updateOrCreate(
                ['cedula_estudiante' => $cedStr, 'codigo_ano_escolar' => self::ANIO_VIGENTE],
                [
                    'codigo_seccion' => $seccion, 'cedula_representante' => $data['representante'],
                    'fecha_matricula' => '2025-09-10', 'numero_lista' => $numLista[$seccion],
                    'condicion_ingreso' => 'Regular', 'procedencia' => 'U.E.E. Nelson Mandela',
                    'ano_inicio_cursante' => 2025, 'estado_matricula' => $estadoMatricula,
                    'observaciones' => $cedStr === '30123123' ? 'Ver fecha y motivo de retiro en ficha del estudiante.' : null,
                    'fecha_retiro' => $data['model']->fecha_retiro,
                    'motivo_retiro' => $data['model']->motivo_retiro,
                ]
            );
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // ── 7. FICHAS ANTROPOMÉTRICAS
    // ══════════════════════════════════════════════════════════════════════

    private function fichasAntropometricas($estudiantes): void
    {
        $fichas = [
            ['30111111', 1.45, 40.5, 'S',  '10', '35'],
            ['E1234567', 1.68, 58.0, 'M',  '30', '41'],
            ['30888888', 1.60, 52.0, 'S',  '28', '38'],
        ];
        foreach ($fichas as [$ced, $est, $peso, $camisa, $pantalon, $zapatos]) {
            FichaAntropometrica::updateOrCreate(
                ['cedula_estudiante' => $ced, 'codigo_ano_escolar' => self::ANIO_VIGENTE],
                ['estatura' => $est, 'peso' => $peso, 'talla_camisa' => $camisa,
                 'talla_pantalon' => $pantalon, 'talla_zapatos' => $zapatos,
                 'fecha_medicion' => '2025-09-20']
            );
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // ── 8. EVALUACIONES (toda la variedad de casos para la defensa)
    // ══════════════════════════════════════════════════════════════════════
    //
    // Casos cubiertos:
    //   1) Literal A perfecto   → Génesis (30111111) MAT: 20, 19, + CAS: 18, 19
    //   2) Reprobado sostenido  → Santiago (30222222) MAT: 8, 9
    //   3) "En Revisión" resuelto → Diego (E1234567) BIO M2: 8 → revisión → 11
    //   4) Reprobado sin resolver → María Fernanda (30888888) QUI M2: 9
    //   5) Literal (Educación Física) → Diego y María Fernanda EDF = 1 (Aprobado)
    //   6) Contabilidad evaluaciones → Andrea y Kevin en CAS e ING
    // ══════════════════════════════════════════════════════════════════════

    private function evaluaciones($ciencias, $contabilidad, $tecnologia): void
    {
        // ── Notas sección 1A (1ER año) ──
        $notas1A = [
            ['30111111', 'MAT', 1, 16, false], ['30111111', 'MAT', 2, 17, false],
            ['30111111', 'CAS', 1, 18, false], ['30111111', 'CAS', 2, 19, false],
            ['30222222', 'MAT', 1, 8,  false], ['30222222', 'MAT', 2, 9,  false],
            ['30222222', 'CAS', 1, 12, false], ['30222222', 'CAS', 2, 13, false],
            ['30333333', 'MAT', 1, 14, false], ['30333333', 'MAT', 2, 15, false],
            ['30333333', 'CAS', 1, 15, false], ['30333333', 'CAS', 2, 16, false],
            ['E1230001', 'MAT', 1, 11, false], ['E1230001', 'MAT', 2, 10, false],
        ];
        
        // Agregar notas completas (3 momentos en todas las materias) para Sofía (30444444) en 1A
        $materias1ER = ['MAT', 'CAS', 'ING', 'GHC', 'ART', 'EDF'];
        foreach ($materias1ER as $mat) {
            foreach ([1, 2, 3] as $mom) {
                $nota = $mat === 'EDF' ? 1 : rand(17, 20); // Excelente estudiante
                $notas1A[] = ['30444444', $mat, $mom, $nota, false];
            }
        }

        foreach ($notas1A as [$ced, $mat, $mom, $nota, $rev]) {
            Evaluacion::updateOrCreate(
                ['cedula_estudiante' => $ced, 'siglas_materia' => $mat, 'id_mencion' => $ciencias->id_mencion,
                 'codigo_grado' => '1ER', 'codigo_ano_escolar' => self::ANIO_VIGENTE, 'numero_momento' => $mom],
                ['nota' => $nota, 'fecha_evaluacion' => $mom === 1 ? '2025-11-25' : ($mom === 2 ? '2026-03-10' : '2026-06-15'),
                 'cedula_docente_evaluador' => 15111111, 'es_revision' => $rev]
            );
        }

        // ── Notas completas sección 1B (1ER año) ──
        $estudiantes1B = ['30555555', '30666666', '30777777'];
        foreach ($estudiantes1B as $ced) {
            foreach ($materias1ER as $mat) {
                foreach ([1, 2, 3] as $mom) {
                    $nota = $mat === 'EDF' ? 1 : rand(12, 18);
                    Evaluacion::updateOrCreate(
                        ['cedula_estudiante' => $ced, 'siglas_materia' => $mat, 'id_mencion' => $ciencias->id_mencion,
                         'codigo_grado' => '1ER', 'codigo_ano_escolar' => self::ANIO_VIGENTE, 'numero_momento' => $mom],
                        ['nota' => $nota, 'fecha_evaluacion' => $mom === 1 ? '2025-11-25' : ($mom === 2 ? '2026-03-10' : '2026-06-15'),
                         'cedula_docente_evaluador' => 15222222, 'es_revision' => false]
                    );
                }
            }
        }


        // ── Notas sección 5A-Ciencias ──
        $notas5Cien = [
            // Caso 3: Diego - BIO M1=17, M2=8 → revisión resuelta a 11
            ['E1234567', 'BIO', 1, 17, false, null],
            ['E1234567', 'BIO', 2, 8,  false, null],
            ['E1234567', 'BIO', 2, 11, true,  'Revisión de examen solicitada por el representante, resuelta a favor del estudiante'],
            // María Fernanda - FIS aprobado, QUI M2 reprobado (caso 4: sin resolver aún)
            ['30888888', 'FIS', 1, 15, false, null],
            ['30888888', 'FIS', 2, 16, false, null],
            ['30888888', 'QUI', 1, 13, false, null],
            ['30888888', 'QUI', 2, 9,  false, null],  // Caso 4: reprobado sin resolver
        ];
        foreach ($notas5Cien as [$ced, $mat, $mom, $nota, $rev, $motivo]) {
            Evaluacion::updateOrCreate(
                ['cedula_estudiante' => $ced, 'siglas_materia' => $mat, 'id_mencion' => $ciencias->id_mencion,
                 'codigo_grado' => '5TO', 'codigo_ano_escolar' => self::ANIO_VIGENTE, 'numero_momento' => $mom],
                ['nota' => $nota, 'fecha_evaluacion' => $mom === 1 ? '2025-11-25' : '2026-03-10',
                 'cedula_docente_evaluador' => $mat === 'BIO' ? 15333333 : 15555555,
                 'es_revision' => $rev, 'motivo_modificacion' => $motivo,
                 'fecha_modificacion' => $rev ? '2026-03-20' : null]
            );
        }

        // Caso 5: Educación Física (tipo Literal) — Diego y María Fernanda
        foreach (['E1234567' => 1, '30888888' => 1] as $ced => $val) {
            $cedStr = (string)$ced;
            Evaluacion::updateOrCreate(
                ['cedula_estudiante' => $cedStr, 'siglas_materia' => 'EDF', 'id_mencion' => $ciencias->id_mencion,
                 'codigo_grado' => '5TO', 'codigo_ano_escolar' => self::ANIO_VIGENTE, 'numero_momento' => 1],
                ['nota' => $val, 'fecha_evaluacion' => '2025-11-25', 'cedula_docente_evaluador' => 15111111]
            );
        }

        // ── Notas sección 5A-Contabilidad ──
        $notas5Cont = [
            ['30999999', 'CAS', 1, 18, false], ['30999999', 'ING', 1, 14, false],
            ['31000000', 'CAS', 1, 10, false], ['31000000', 'ING', 1, 9,  false],
        ];
        foreach ($notas5Cont as [$ced, $mat, $mom, $nota, $rev]) {
            Evaluacion::updateOrCreate(
                ['cedula_estudiante' => $ced, 'siglas_materia' => $mat, 'id_mencion' => $contabilidad->id_mencion,
                 'codigo_grado' => '5TO', 'codigo_ano_escolar' => self::ANIO_VIGENTE, 'numero_momento' => $mom],
                ['nota' => $nota, 'fecha_evaluacion' => '2025-11-25',
                 'cedula_docente_evaluador' => $mat === 'CAS' ? 15222222 : 15444444, 'es_revision' => $rev]
            );
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // ── 9. MATERIA PENDIENTE
    // ══════════════════════════════════════════════════════════════════════

    private function materiasPendientes($ciencias): void
    {
        // Diego: Física pendiente de 4TO (año anterior) — estado PENDIENTE
        MateriaPendiente::updateOrCreate(
            ['cedula_estudiante' => 'E1234567', 'siglas_materia' => 'FIS', 'id_mencion' => $ciencias->id_mencion,
             'codigo_grado' => '4TO', 'codigo_ano_escolar_origen' => self::ANIO_ANTERIOR],
            ['estado' => 'pendiente', 'fecha_resolucion' => null, 'nota_final' => null]
        );
        // María Fernanda: Física de 4TO (año anterior) — estado APROBADA (ya la reparó)
        MateriaPendiente::updateOrCreate(
            ['cedula_estudiante' => '30888888', 'siglas_materia' => 'FIS', 'id_mencion' => $ciencias->id_mencion,
             'codigo_grado' => '4TO', 'codigo_ano_escolar_origen' => self::ANIO_ANTERIOR],
            ['estado' => 'aprobada', 'fecha_resolucion' => '2025-10-15', 'nota_final' => 12]
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // ── 10. DOCUMENTOS EMITIDOS
    // ══════════════════════════════════════════════════════════════════════

    private function documentosEmitidos($usuarios): void
    {
        // contenido_pdf es VARBINARY(MAX) NOT NULL. 
        // Usamos DB::raw y CONVERT porque PDO SQLSRV falla al bindear binarios puros por defecto.
        $pdfPlaceholder = \Illuminate\Support\Facades\DB::raw("CONVERT(varbinary(max), '255044462D312E300A', 2)"); // "%PDF-1.0\n"

        $docs = [
            ['boletin',           '30111111',  1,    'BOL-2025-0001', 20],
            ['boletin',           '30222222',  1,    'BOL-2025-0002', 20],
            ['boletin',           '30444444',  3,    'BOL-2025-0003',  1], // Sofía
            ['constancia',        '30444444',  null, 'CE-2025-0001',  10],
            ['notas_certificadas','E1234567',  null, 'NC-2025-0001',  5],
            ['resumen_final',     '30888888',  null, 'REV-2025-0001', 2],
        ];
        foreach ($docs as [$tipo, $ced, $mom, $folio, $diasAtras]) {
            DocumentoEmitido::updateOrCreate(['folio' => $folio], [
                'tipo_documento' => $tipo, 'cedula_estudiante' => $ced,
                'codigo_ano_escolar' => self::ANIO_VIGENTE,
                'numero_momento' => $mom,
                'id_usuario_emisor' => $usuarios['control']->id_usuario,
                'fecha_emision' => now()->subDays($diasAtras),
                'contenido_pdf' => $pdfPlaceholder,
            ]);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // ── 11. LOGINS Y AUDITORÍA
    // ══════════════════════════════════════════════════════════════════════

    private function loginsYAuditoria($usuarios): void
    {
        // 5 días de logins exitosos para admin y docente1
        foreach ([5, 4, 3, 2, 1] as $diasAtras) {
            LoginRecord::updateOrCreate(
                ['id_usuario' => $usuarios['admin']->id_usuario, 'fecha' => now()->subDays($diasAtras)->toDateString(), 'hora' => '07:45:00'],
                ['ip_acceso' => '192.168.1.10', 'tipo_acceso' => 'E', 'exitoso' => true]
            );
            LoginRecord::updateOrCreate(
                ['id_usuario' => $usuarios['docente1']->id_usuario, 'fecha' => now()->subDays($diasAtras)->toDateString(), 'hora' => '08:10:00'],
                ['ip_acceso' => '192.168.1.15', 'tipo_acceso' => 'E', 'exitoso' => true]
            );
        }
        // Intento FALLIDO de control de estudios (hace 2 días)
        LoginRecord::updateOrCreate(
            ['id_usuario' => $usuarios['control']->id_usuario, 'fecha' => now()->subDays(2)->toDateString(), 'hora' => '07:50:00'],
            ['ip_acceso' => '192.168.1.20', 'tipo_acceso' => 'E', 'exitoso' => false]
        );

        // Generar algunas filas de auditoría simuladas para la demo del video
        $fechas = [
            now()->subDays(1)->setTime(10, 30, 0),
            now()->subDays(1)->setTime(11, 45, 0),
            now()->subMinutes(120),
            now()->subMinutes(45),
            now()->subMinutes(15),
        ];

        $auditData = [
            ['Estudiante', '30444444', 'U', null, '{"direccion": "Charallave Centro"}', $usuarios['admin']->id_usuario, $fechas[0]],
            ['Materia', 'MAT', 'U', null, '{"horas_semanales": 6}', $usuarios['control']->id_usuario, $fechas[1]],
            ['Seccion', '1A-2025', 'I', null, '{"capacidad_maxima": 35}', $usuarios['admin']->id_usuario, $fechas[2]],
            ['Institucion', '1', 'U', null, '{"telefono": "0239-5555555"}', $usuarios['admin']->id_usuario, $fechas[3]],
            ['Evaluacion', 'EVAL-001', 'U', '{"nota": 12}', '{"nota": 18}', $usuarios['docente1']->id_usuario, $fechas[4]],
        ];

        foreach ($auditData as [$tabla, $idReg, $op, $ant, $nuev, $usrId, $fh]) {
            Auditoria::create([
                'tabla_afectada' => $tabla,
                'id_registro_afectado' => $idReg,
                'operacion' => $op,
                'valores_anteriores' => $ant,
                'valores_nuevos' => $nuev,
                'id_usuario' => $usrId,
                'fecha_hora' => $fh,
            ]);
        }
    }
}
