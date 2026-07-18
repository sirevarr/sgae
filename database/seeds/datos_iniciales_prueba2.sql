-- ============================================================
--  DATOS INICIALES PARA SGAE — Base de datos: prueba2
--  Ejecutar DESPUÉS de crear las tablas con el DDL del schema.
-- ============================================================

USE prueba2;
GO

-- ── 1. PARÁMETROS DEL SISTEMA ────────────────────────────────
INSERT INTO Parametro_Sistema (clave, valor) VALUES
    ('nota_minima_aprobatoria', '10'),
    ('nombre_sistema', 'SGAE'),
    ('version', '1.0.0'),
    ('max_estudiantes_seccion', '35');
GO

-- ── 2. INSTITUCIÓN ───────────────────────────────────────────
-- REEMPLAZA estos datos con los de tu institución
INSERT INTO Institucion (codigo_dea, nombre, municipio, estado, zona_educativa, telefono, direccion)
VALUES (
    'DE-XXXX',
    'U.E. NOMBRE DE TU INSTITUCIÓN',
    'Tu Municipio',
    'Tu Estado',
    'Zona Educativa N°X',
    '0212-0000000',
    'Dirección completa de la institución'
);
GO

-- ── 3. AÑO ESCOLAR ───────────────────────────────────────────
INSERT INTO Anio_Escolar (codigo_ano_escolar, fecha_inicio, fecha_fin, estado)
VALUES ('2025-2026', '2025-09-15', '2026-07-15', 'vigente');
GO

-- ── 4. GRADOS (Media General venezolana) ─────────────────────
INSERT INTO Grado (codigo_grado, nombre, nivel_educativo, numero_ano, estado) VALUES
    ('1ER', 'Primer Año',   'Educación Media General', 1, 'activo'),
    ('2DO', 'Segundo Año',  'Educación Media General', 2, 'activo'),
    ('3ER', 'Tercer Año',   'Educación Media General', 3, 'activo'),
    ('4TO', 'Cuarto Año',   'Educación Media General', 4, 'activo'),
    ('5TO', 'Quinto Año',   'Educación Media General', 5, 'activo');
GO

-- ── 5. MENCIONES ─────────────────────────────────────────────
INSERT INTO Mencion (nombre, estado) VALUES
    ('Ciencias',   'activo'),
    ('Humanidades','activo'),
    ('Comercio',   'activo');
GO

-- ── 6. MATERIAS (ejemplo Media General venezolana) ───────────
INSERT INTO Materia (siglas, nombre, area_formacion) VALUES
    ('MAT', 'Matemática',                    'Matemática'),
    ('CAS', 'Castellano y Literatura',       'Castellano y Literatura'),
    ('ING', 'Idiomas Extranjeros (Inglés)',  'Idiomas Extranjeros'),
    ('BIO', 'Biología',                      'Ciencias Naturales'),
    ('FIS', 'Física',                        'Ciencias Naturales'),
    ('QUI', 'Química',                       'Ciencias Naturales'),
    ('HIS', 'Historia',                      'Ciencias Sociales'),
    ('GEO', 'Geografía',                     'Ciencias Sociales'),
    ('EDU', 'Educación para el Trabajo',     'Educación para el Trabajo'),
    ('EDF', 'Educación Física',              'Educación Física'),
    ('ART', 'Arte y Patrimonio',             'Arte y Patrimonio'),
    ('ORI', 'Orientación',                   'Orientación'),
    ('ADM', 'Administración',                'Educación para el Trabajo'),
    ('CON', 'Contabilidad',                  'Ciencias Económicas'),
    ('FIL', 'Filosofía',                     'Filosofía');
GO

-- ── 7. PERSONAL ADMINISTRATIVO (datos de ejemplo) ────────────
-- Ajusta cédula y nombres con los datos reales del director
INSERT INTO Personal (cedula_personal, nombres, apellidos, cargo, genero, estado)
VALUES
    (12345678, 'María', 'González', 'Directora', 'F', 'activo'),
    (87654321, 'Juan',  'Pérez',    'Coordinador Académico', 'M', 'activo');
GO

-- Actualizar institución con el director
UPDATE Institucion SET
    director_actual       = 12345678,
    coordinador_academico = 87654321
WHERE codigo_dea = 'DE-XXXX';
GO

-- ── 8. USUARIO ADMINISTRADOR ──────────────────────────────────
-- IMPORTANTE: La clave_hash es un bcrypt de 'Admin2025!'
-- Cámbiala inmediatamente al iniciar sesión, o genera una nueva con:
--   php artisan tinker → Hash::make('tu_nueva_clave')
INSERT INTO Usuario (codigo_usuario, cedula_personal, rol, clave_hash, estado, fecha_creacion, intentos_fallidos)
VALUES (
    'admin',
    12345678,
    'administrador',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- 'password'
    'activo',
    CAST(GETDATE() AS DATE),
    0
);
GO

-- ── 9. MOMENTOS EVALUATIVOS ───────────────────────────────────
INSERT INTO Momento_Evaluativo (numero_momento, codigo_ano_escolar, nombre, fecha_inicio, fecha_fin, porcentaje, estado) VALUES
    (1, '2025-2026', 'Primer Momento',   '2025-09-15', '2025-11-30', 33.33, 'activo'),
    (2, '2025-2026', 'Segundo Momento',  '2025-12-01', '2026-03-15', 33.33, 'por_iniciar'),
    (3, '2025-2026', 'Tercer Momento',   '2026-03-16', '2026-07-15', 33.34, 'por_iniciar');
GO

-- ── 10. EJEMPLO DE SECCIÓN (Primer Año A) ────────────────────
INSERT INTO Seccion (codigo_seccion, letra, codigo_grado, codigo_ano_escolar, capacidad_maxima, turno)
VALUES ('1A-2025', 'A', '1ER', '2025-2026', 35, 'mañana');
GO

PRINT '✓ Datos iniciales insertados correctamente en prueba2.';
PRINT '  Usuario: admin  |  Contraseña: password';
PRINT '  CAMBIA LA CONTRASEÑA AL PRIMER INICIO DE SESIÓN.';
GO
