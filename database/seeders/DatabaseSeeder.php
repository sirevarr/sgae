<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\ParametroSistema;
use App\Models\Institucion;
use App\Models\AnioEscolar;
use App\Models\Grado;
use App\Models\Mencion;
use App\Models\Materia;
use App\Models\Personal;
use App\Models\Usuario;
use App\Models\MomentoEvaluativo;
use App\Models\Seccion;

class DatabaseSeeder extends Seeder
{
    private const ANIO_ESCOLAR = '2025-2026';
    private const NIVEL_MEDIA_GENERAL = 'Educación Media General';
    private const AREA_CIENCIAS_NATURALES = 'Ciencias Naturales';
    private const ESTADO_ACTIVO = 'activo';
    private const ESTADO_VIGENTE = 'vigente';
    private const CLAVE_NOTA_MINIMA = 'nota_minima_aprobatoria';
    private const CLAVE_NOMBRE_SISTEMA = 'nombre_sistema';
    private const CLAVE_VERSION = 'version';
    private const CLAVE_MAX_ESTUDIANTES = 'max_estudiantes_seccion';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Parámetros del Sistema
        $this->crearParametrosSistema();

        // 2. Personal Administrativo (Director y Coordinador)
        $director = Personal::updateOrCreate(
            ['cedula_personal' => 12345678],
            [
                'nombres' => 'María',
                'apellidos' => 'González',
                'cargo' => 'Directora',
                'genero' => 'F',
                'estado' => self::ESTADO_ACTIVO,
            ]
        );

        $coordinador = Personal::updateOrCreate(
            ['cedula_personal' => 87654321],
            [
                'nombres' => 'Juan',
                'apellidos' => 'Pérez',
                'cargo' => 'Coordinador Académico',
                'genero' => 'M',
                'estado' => self::ESTADO_ACTIVO,
            ]
        );

        // 3. Institución
        Institucion::updateOrCreate(
            ['codigo_dea' => 'DE-XXXX'],
            [
                'nombre' => 'U.E. NOMBRE DE TU INSTITUCIÓN',
                'municipio' => 'Tu Municipio',
                'estado' => 'Tu Estado',
                'zona_educativa' => 'Zona Educativa N°X',
                'telefono' => '0212-0000000',
                'direccion' => 'Dirección completa de la institución',
                'director_actual' => $director->cedula_personal,
                'coordinador_academico' => $coordinador->cedula_personal,
            ]
        );

        // 4. Año Escolar
        AnioEscolar::updateOrCreate(
            ['codigo_ano_escolar' => self::ANIO_ESCOLAR],
            [
                'fecha_inicio' => '2025-09-15',
                'fecha_fin' => '2026-07-15',
                'estado' => self::ESTADO_VIGENTE,
            ]
        );

        // 5. Grados (Media General venezolana)
        $grados = [
            ['codigo_grado' => '1ER', 'nombre' => 'Primer Año', 'nivel_educativo' => self::NIVEL_MEDIA_GENERAL, 'numero_ano' => 1, 'estado' => 'activo'],
            ['codigo_grado' => '2DO', 'nombre' => 'Segundo Año', 'nivel_educativo' => self::NIVEL_MEDIA_GENERAL, 'numero_ano' => 2, 'estado' => 'activo'],
            ['codigo_grado' => '3ER', 'nombre' => 'Tercer Año', 'nivel_educativo' => self::NIVEL_MEDIA_GENERAL, 'numero_ano' => 3, 'estado' => 'activo'],
            ['codigo_grado' => '4TO', 'nombre' => 'Cuarto Año', 'nivel_educativo' => self::NIVEL_MEDIA_GENERAL, 'numero_ano' => 4, 'estado' => 'activo'],
            ['codigo_grado' => '5TO', 'nombre' => 'Quinto Año', 'nivel_educativo' => self::NIVEL_MEDIA_GENERAL, 'numero_ano' => 5, 'estado' => 'activo'],
        ];
        foreach ($grados as $g) {
            Grado::updateOrCreate(['codigo_grado' => $g['codigo_grado']], $g);
        }

        // 6. Menciones
        $menciones = [
            ['nombre' => 'Ciencias', 'estado' => 'activo'],
            ['nombre' => 'Humanidades', 'estado' => 'activo'],
            ['nombre' => 'Comercio', 'estado' => 'activo'],
        ];
        foreach ($menciones as $m) {
            Mencion::updateOrCreate(['nombre' => $m['nombre']], $m);
        }

        // 7. Materias
        $materias = [
            ['siglas' => 'MAT', 'nombre' => 'Matemática', 'area_formacion' => 'Matemática'],
            ['siglas' => 'CAS', 'nombre' => 'Castellano y Literatura', 'area_formacion' => 'Castellano y Literatura'],
            ['siglas' => 'ING', 'nombre' => 'Idiomas Extranjeros (Inglés)', 'area_formacion' => 'Idiomas Extranjeros'],
            ['siglas' => 'BIO', 'nombre' => 'Biología', 'area_formacion' => self::AREA_CIENCIAS_NATURALES],
            ['siglas' => 'FIS', 'nombre' => 'Física', 'area_formacion' => self::AREA_CIENCIAS_NATURALES],
            ['siglas' => 'QUI', 'nombre' => 'Química', 'area_formacion' => self::AREA_CIENCIAS_NATURALES],
            ['siglas' => 'HIS', 'nombre' => 'Historia', 'area_formacion' => 'Ciencias Sociales'],
            ['siglas' => 'GEO', 'nombre' => 'Geografía', 'area_formacion' => 'Ciencias Sociales'],
            ['siglas' => 'EDU', 'nombre' => 'Educación para el Trabajo', 'area_formacion' => 'Educación para el Trabajo'],
            ['siglas' => 'EDF', 'nombre' => 'Educación Física', 'area_formacion' => 'Educación Física'],
            ['siglas' => 'ART', 'nombre' => 'Arte y Patrimonio', 'area_formacion' => 'Arte y Patrimonio'],
            ['siglas' => 'ORI', 'nombre' => 'Orientación', 'area_formacion' => 'Orientación'],
        ];
        foreach ($materias as $mat) {
            Materia::updateOrCreate(['siglas' => $mat['siglas']], $mat);
        }

        // 8. Usuario Administrador (password: 'password')
        Usuario::updateOrCreate(
            ['codigo_usuario' => 'admin'],
            [
                'name' => 'Administrador',
                'email' => 'admin@sgae.test',
                'email_verified_at' => now(),
                'cedula_personal' => $director->cedula_personal,
                'rol' => 'administrador',
                'clave_hash' => Hash::make('password'),
                'estado' => self::ESTADO_ACTIVO,
                'fecha_creacion' => now()->format('Y-m-d'),
                'intentos_fallidos' => 0,
            ]
        );

        // 9. Momentos Evaluativos
        $momentos = [
            ['numero_momento' => 1, 'codigo_ano_escolar' => self::ANIO_ESCOLAR, 'nombre' => 'Primer Momento', 'fecha_inicio' => '2025-09-15', 'fecha_fin' => '2025-11-30', 'porcentaje' => 33.33, 'estado' => 'activo'],
            ['numero_momento' => 2, 'codigo_ano_escolar' => self::ANIO_ESCOLAR, 'nombre' => 'Segundo Momento', 'fecha_inicio' => '2025-12-01', 'fecha_fin' => '2026-03-15', 'porcentaje' => 33.33, 'estado' => 'por_iniciar'],
            ['numero_momento' => 3, 'codigo_ano_escolar' => self::ANIO_ESCOLAR, 'nombre' => 'Tercer Momento', 'fecha_inicio' => '2026-03-16', 'fecha_fin' => '2026-07-15', 'porcentaje' => 33.34, 'estado' => 'por_iniciar'],
        ];
        foreach ($momentos as $mom) {
            MomentoEvaluativo::updateOrCreate(
                ['numero_momento' => $mom['numero_momento'], 'codigo_ano_escolar' => $mom['codigo_ano_escolar']],
                $mom
            );
        }

        // 10. Mencion de prueba
        $mencionCiencias = Mencion::where('nombre', 'Ciencias')->first();
        $idMencion = $mencionCiencias ? $mencionCiencias->id_mencion : 1;

        // 11. Ejemplo de Sección
        Seccion::updateOrCreate(
            ['codigo_seccion' => '1A-2025'],
            [
                'letra' => 'A',
                'codigo_grado' => '1ER',
                'codigo_ano_escolar' => self::ANIO_ESCOLAR,
                'id_mencion' => $idMencion,
                'capacidad_maxima' => 35,
                'turno' => 'mañana'
            ]
        );

        // 12. Población del Plan de Estudios para permitir guardar notas en la base de datos
        // (SQL Server requiere FK compuesta apuntando a Plan_Estudios)
        foreach ($materias as $mat) {
            \App\Models\PlanEstudios::updateOrCreate(
                [
                    'siglas_materia' => $mat['siglas'],
                    'id_mencion' => $idMencion,
                    'codigo_grado' => '1ER',
                    'codigo_ano_escolar' => self::ANIO_ESCOLAR,
                ],
                [
                    'horas_semanales' => 4,
                    'obligatoria' => true,
                    'tipo_evaluacion' => 'N',
                    'se_repara' => true,
                    'creditos' => 1,
                    'estado' => self::ESTADO_ACTIVO,
                ]
            );
        }
    }

    private function crearParametrosSistema(): void
    {
        $parametros = [
            self::CLAVE_NOTA_MINIMA => '10',
            self::CLAVE_NOMBRE_SISTEMA => 'SGAE',
            self::CLAVE_VERSION => '1.0.0',
            self::CLAVE_MAX_ESTUDIANTES => '35',
        ];

        foreach ($parametros as $clave => $valor) {
            ParametroSistema::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        }
    }
}
