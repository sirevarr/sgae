SET NOCOUNT ON;

-- Drop views first
IF OBJECT_ID('dbo.vw_Evaluacion_Resultado', 'V') IS NOT NULL
    DROP VIEW dbo.vw_Evaluacion_Resultado;
IF OBJECT_ID('dbo.vw_Seccion_Conteo', 'V') IS NOT NULL
    DROP VIEW dbo.vw_Seccion_Conteo;

-- Drop tables in dependency order
IF OBJECT_ID('dbo.Documento_Emitido', 'U') IS NOT NULL DROP TABLE dbo.Documento_Emitido;
IF OBJECT_ID('dbo.Materia_Pendiente', 'U') IS NOT NULL DROP TABLE dbo.Materia_Pendiente;
IF OBJECT_ID('dbo.Evaluacion', 'U') IS NOT NULL DROP TABLE dbo.Evaluacion;
IF OBJECT_ID('dbo.Matricula', 'U') IS NOT NULL DROP TABLE dbo.Matricula;
IF OBJECT_ID('dbo.Asignacion_Docente', 'U') IS NOT NULL DROP TABLE dbo.Asignacion_Docente;
IF OBJECT_ID('dbo.Seccion', 'U') IS NOT NULL DROP TABLE dbo.Seccion;
IF OBJECT_ID('dbo.Plan_Estudios', 'U') IS NOT NULL DROP TABLE dbo.Plan_Estudios;
IF OBJECT_ID('dbo.Ficha_Antropometrica', 'U') IS NOT NULL DROP TABLE dbo.Ficha_Antropometrica;
IF OBJECT_ID('dbo.Representante', 'U') IS NOT NULL DROP TABLE dbo.Representante;
IF OBJECT_ID('dbo.Auditoria', 'U') IS NOT NULL DROP TABLE dbo.Auditoria;
IF OBJECT_ID('dbo.Login', 'U') IS NOT NULL DROP TABLE dbo.Login;
IF OBJECT_ID('dbo.Usuario', 'U') IS NOT NULL DROP TABLE dbo.Usuario;
IF OBJECT_ID('dbo.Institucion', 'U') IS NOT NULL DROP TABLE dbo.Institucion;
IF OBJECT_ID('dbo.Docente', 'U') IS NOT NULL DROP TABLE dbo.Docente;
IF OBJECT_ID('dbo.Personal', 'U') IS NOT NULL DROP TABLE dbo.Personal;
IF OBJECT_ID('dbo.Estudiante', 'U') IS NOT NULL DROP TABLE dbo.Estudiante;
IF OBJECT_ID('dbo.Materia', 'U') IS NOT NULL DROP TABLE dbo.Materia;
IF OBJECT_ID('dbo.Mencion', 'U') IS NOT NULL DROP TABLE dbo.Mencion;
IF OBJECT_ID('dbo.Grado', 'U') IS NOT NULL DROP TABLE dbo.Grado;
IF OBJECT_ID('dbo.Anio_Escolar', 'U') IS NOT NULL DROP TABLE dbo.Anio_Escolar;
IF OBJECT_ID('dbo.Parametro_Sistema', 'U') IS NOT NULL DROP TABLE dbo.Parametro_Sistema;

-- Core tables
CREATE TABLE Personal (
    cedula_personal   BIGINT       PRIMARY KEY,
    nombres           VARCHAR(80)  NOT NULL,
    apellidos         VARCHAR(80)  NOT NULL,
    cargo             VARCHAR(80),
    telefono          VARCHAR(20),
    correo            VARCHAR(120),
    fecha_nacimiento  DATE,
    genero            CHAR(1),
    fecha_ingreso     DATE,
    estado            VARCHAR(20)  DEFAULT 'activo'
                       CHECK (estado IN ('activo','inactivo','archivado')),
    observaciones     VARCHAR(MAX)
);

CREATE TABLE Docente (
    cedula_personal   BIGINT PRIMARY KEY REFERENCES Personal(cedula_personal),
    especialidad      VARCHAR(80),
    turno             CHAR(1) CHECK (turno IN ('M','T','N'))
);

CREATE TABLE Institucion (
    codigo_dea              VARCHAR(20)  PRIMARY KEY,
    nombre                  VARCHAR(150) NOT NULL,
    direccion               VARCHAR(200),
    telefono                VARCHAR(20),
    municipio               VARCHAR(80),
    estado                  VARCHAR(80),
    zona_educativa          VARCHAR(80),
    director_actual         BIGINT REFERENCES Personal(cedula_personal),
    coordinador_academico   BIGINT REFERENCES Personal(cedula_personal)
);

CREATE TABLE Usuario (
    id_usuario        INT IDENTITY(1,1) PRIMARY KEY,
    codigo_usuario    VARCHAR(30) UNIQUE NOT NULL,
    cedula_personal   BIGINT REFERENCES Personal(cedula_personal),
    rol               VARCHAR(30) NOT NULL,
    clave_hash        VARCHAR(255) NOT NULL,
    estado            VARCHAR(20) DEFAULT 'activo'
                       CHECK (estado IN ('activo','inactivo','bloqueado')),
    fecha_creacion    DATE DEFAULT CAST(GETDATE() AS DATE),
    ultimo_acceso     DATE,
    intentos_fallidos INT DEFAULT 0
);

CREATE TABLE Login (
    id_login    INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario  INT NOT NULL REFERENCES Usuario(id_usuario),
    fecha       DATE NOT NULL,
    hora        TIME NOT NULL,
    ip_acceso   VARCHAR(45),
    tipo_acceso CHAR(1) CHECK (tipo_acceso IN ('E','S')),
    exitoso     BIT
);

CREATE TABLE Auditoria (
    id_auditoria          INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario            INT NOT NULL REFERENCES Usuario(id_usuario),
    tabla_afectada        VARCHAR(60) NOT NULL,
    id_registro_afectado  VARCHAR(60) NOT NULL,
    operacion             CHAR(1) NOT NULL CHECK (operacion IN ('I','U','D')),
    fecha_hora            DATETIME2 DEFAULT SYSDATETIME(),
    valores_anteriores    NVARCHAR(MAX),
    valores_nuevos        NVARCHAR(MAX)
);

CREATE TABLE Grado (
    codigo_grado    VARCHAR(10) PRIMARY KEY,
    nombre          VARCHAR(60) NOT NULL,
    nivel_educativo VARCHAR(40),
    numero_ano      INT,
    estado          VARCHAR(20) DEFAULT 'activo'
                     CHECK (estado IN ('activo','inactivo'))
);

CREATE TABLE Mencion (
    id_mencion   INT IDENTITY(1,1) PRIMARY KEY,
    nombre       VARCHAR(80) NOT NULL,
    estado       VARCHAR(20) DEFAULT 'activo'
                  CHECK (estado IN ('activo','inactivo'))
);

CREATE TABLE Anio_Escolar (
    codigo_ano_escolar VARCHAR(10) PRIMARY KEY,
    fecha_inicio        DATE,
    fecha_fin           DATE,
    estado              VARCHAR(20) DEFAULT 'planificado'
                         CHECK (estado IN ('planificado','vigente','cerrado'))
);

CREATE TABLE Parametro_Sistema (
    clave  VARCHAR(40) PRIMARY KEY,
    valor  VARCHAR(100) NOT NULL
);

CREATE TABLE Materia (
    siglas          VARCHAR(10) PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    area_formacion  VARCHAR(60)
);

CREATE TABLE Plan_Estudios (
    siglas_materia         VARCHAR(10) NOT NULL REFERENCES Materia(siglas),
    id_mencion             INT         NOT NULL REFERENCES Mencion(id_mencion),
    codigo_grado           VARCHAR(10) NOT NULL REFERENCES Grado(codigo_grado),
    codigo_ano_escolar     VARCHAR(10) NOT NULL REFERENCES Anio_Escolar(codigo_ano_escolar),
    horas_semanales        INT,
    obligatoria            BIT DEFAULT 1,
    tipo_evaluacion        CHAR(1) CHECK (tipo_evaluacion IN ('N','L')),
    se_repara              BIT DEFAULT 1,
    creditos               INT,
    estado                 VARCHAR(20) DEFAULT 'activo',
    PRIMARY KEY (siglas_materia, id_mencion, codigo_grado, codigo_ano_escolar)
);

CREATE TABLE Seccion (
    codigo_seccion       VARCHAR(15) PRIMARY KEY,
    letra                CHAR(1) NOT NULL,
    codigo_grado         VARCHAR(10) NOT NULL REFERENCES Grado(codigo_grado),
    codigo_ano_escolar   VARCHAR(10) NOT NULL REFERENCES Anio_Escolar(codigo_ano_escolar),
    id_mencion           INT REFERENCES Mencion(id_mencion),
    cedula_docente_guia  BIGINT REFERENCES Docente(cedula_personal),
    capacidad_maxima     INT,
    turno                CHAR(1) CHECK (turno IN ('M','T','N')),
    aula_asignada        VARCHAR(20)
);

CREATE TABLE Asignacion_Docente (
    id_asignacion       INT IDENTITY(1,1) PRIMARY KEY,
    cedula_docente      BIGINT      NOT NULL REFERENCES Docente(cedula_personal),
    codigo_seccion      VARCHAR(15) NOT NULL REFERENCES Seccion(codigo_seccion),
    siglas_materia      VARCHAR(10) NOT NULL,
    id_mencion          INT         NOT NULL,
    codigo_grado        VARCHAR(10) NOT NULL,
    codigo_ano_escolar  VARCHAR(10) NOT NULL,
    horas_asignadas     INT,
    FOREIGN KEY (siglas_materia, id_mencion, codigo_grado, codigo_ano_escolar)
        REFERENCES Plan_Estudios(siglas_materia, id_mencion, codigo_grado, codigo_ano_escolar),
    CONSTRAINT UQ_Asignacion_Sin_Duplicados
        UNIQUE (cedula_docente, codigo_seccion, siglas_materia, codigo_ano_escolar)
);

CREATE TABLE Estudiante (
    cedula_estudiante    VARCHAR(20) PRIMARY KEY,
    tipo_documento       CHAR(1) NOT NULL DEFAULT 'V'
                          CHECK (tipo_documento IN ('V','E')),
    nacionalidad         VARCHAR(30),
    nombres              VARCHAR(80) NOT NULL,
    apellidos            VARCHAR(80) NOT NULL,
    genero               CHAR(1),
    fecha_nacimiento     DATE,
    lugar_nacimiento     VARCHAR(80),
    estado_nacimiento    VARCHAR(60),
    municipio_nacimiento VARCHAR(60),
    direccion            VARCHAR(200),
    telefono             VARCHAR(20),
    correo               VARCHAR(120),
    condiciones_medicas  VARCHAR(MAX),
    medicamentos         VARCHAR(MAX),
    fecha_ingreso        DATE,
    estado_estudiante    VARCHAR(20) DEFAULT 'activo'
                          CHECK (estado_estudiante IN ('activo','retirado','graduado')),
    fecha_retiro         DATE,
    motivo_retiro        VARCHAR(200)
);

CREATE TABLE Ficha_Antropometrica (
    cedula_estudiante   VARCHAR(20) NOT NULL REFERENCES Estudiante(cedula_estudiante),
    codigo_ano_escolar  VARCHAR(10) NOT NULL REFERENCES Anio_Escolar(codigo_ano_escolar),
    estatura            FLOAT,
    peso                FLOAT,
    talla_camisa        VARCHAR(10),
    talla_pantalon      VARCHAR(10),
    talla_zapatos       VARCHAR(10),
    fecha_medicion      DATE,
    PRIMARY KEY (cedula_estudiante, codigo_ano_escolar)
);

CREATE TABLE Representante (
    cedula_representante     BIGINT PRIMARY KEY,
    nacionalidad             VARCHAR(30),
    nombres                  VARCHAR(80) NOT NULL,
    apellidos                VARCHAR(80) NOT NULL,
    parentesco               VARCHAR(40),
    ocupacion                VARCHAR(80),
    direccion                VARCHAR(200),
    telefono                 VARCHAR(20),
    correo                   VARCHAR(120),
    es_representante_legal   BIT DEFAULT 0
);

CREATE TABLE Matricula (
    id_matricula          INT IDENTITY(1,1) PRIMARY KEY,
    cedula_estudiante     VARCHAR(20) NOT NULL REFERENCES Estudiante(cedula_estudiante),
    codigo_ano_escolar    VARCHAR(10) NOT NULL REFERENCES Anio_Escolar(codigo_ano_escolar),
    codigo_seccion        VARCHAR(15) NOT NULL REFERENCES Seccion(codigo_seccion),
    cedula_representante  BIGINT REFERENCES Representante(cedula_representante),
    fecha_matricula       DATE NOT NULL,
    numero_lista          INT,
    condicion_ingreso     VARCHAR(40),
    procedencia           VARCHAR(120),
    ano_inicio_cursante   INT,
    estado_matricula      VARCHAR(20) DEFAULT 'activa'
                           CHECK (estado_matricula IN ('activa','retirada','trasladada')),
    observaciones         VARCHAR(MAX),
    fecha_retiro          DATE,
    motivo_retiro         VARCHAR(200)
);

CREATE TABLE Momento_Evaluativo (
    numero_momento      INT NOT NULL,
    codigo_ano_escolar  VARCHAR(10) NOT NULL REFERENCES Anio_Escolar(codigo_ano_escolar),
    nombre              VARCHAR(60),
    fecha_inicio        DATE,
    fecha_fin           DATE,
    porcentaje          FLOAT,
    estado              VARCHAR(20) DEFAULT 'planificado',
    PRIMARY KEY (numero_momento, codigo_ano_escolar)
);

CREATE TABLE Evaluacion (
    id_evaluacion             INT IDENTITY(1,1) PRIMARY KEY,
    cedula_estudiante         VARCHAR(20) NOT NULL REFERENCES Estudiante(cedula_estudiante),
    siglas_materia            VARCHAR(10) NOT NULL,
    id_mencion                INT         NOT NULL,
    codigo_grado              VARCHAR(10) NOT NULL,
    codigo_ano_escolar        VARCHAR(10) NOT NULL,
    numero_momento            INT         NOT NULL,
    nota                      FLOAT,
    fecha_evaluacion          DATE,
    es_revision               BIT DEFAULT 0,
    cedula_docente_evaluador  BIGINT REFERENCES Docente(cedula_personal),
    fecha_modificacion        DATE,
    motivo_modificacion       VARCHAR(200),
    FOREIGN KEY (siglas_materia, id_mencion, codigo_grado, codigo_ano_escolar)
        REFERENCES Plan_Estudios(siglas_materia, id_mencion, codigo_grado, codigo_ano_escolar),
    FOREIGN KEY (numero_momento, codigo_ano_escolar)
        REFERENCES Momento_Evaluativo(numero_momento, codigo_ano_escolar)
);

CREATE TABLE Materia_Pendiente (
    id_materia_pendiente      INT IDENTITY(1,1) PRIMARY KEY,
    cedula_estudiante         VARCHAR(20) NOT NULL REFERENCES Estudiante(cedula_estudiante),
    siglas_materia            VARCHAR(10) NOT NULL,
    id_mencion                INT         NOT NULL,
    codigo_grado              VARCHAR(10) NOT NULL,
    codigo_ano_escolar_origen VARCHAR(10) NOT NULL REFERENCES Anio_Escolar(codigo_ano_escolar),
    estado                    VARCHAR(20) DEFAULT 'pendiente'
                               CHECK (estado IN ('pendiente','aprobada','no_aprobada')),
    fecha_resolucion          DATE,
    nota_final                FLOAT,
    FOREIGN KEY (siglas_materia, id_mencion, codigo_grado, codigo_ano_escolar_origen)
        REFERENCES Plan_Estudios(siglas_materia, id_mencion, codigo_grado, codigo_ano_escolar)
);

CREATE TABLE Documento_Emitido (
    id_documento        INT IDENTITY(1,1) PRIMARY KEY,
    tipo_documento       VARCHAR(40)  NOT NULL
                          CHECK (tipo_documento IN ('boletin','constancia','notas_certificadas','resumen_final')),
    cedula_estudiante    VARCHAR(20)  NOT NULL REFERENCES Estudiante(cedula_estudiante),
    codigo_ano_escolar   VARCHAR(10)  REFERENCES Anio_Escolar(codigo_ano_escolar),
    numero_momento       INT          NULL,
    folio                VARCHAR(30)  NOT NULL UNIQUE,
    id_usuario_emisor    INT          NOT NULL REFERENCES Usuario(id_usuario),
    fecha_emision        DATETIME2    DEFAULT SYSDATETIME(),
    contenido_pdf        VARBINARY(MAX) NOT NULL
);

CREATE VIEW vw_Seccion_Conteo AS
SELECT
    m.codigo_seccion,
    COUNT(*) AS total_estudiantes,
    SUM(CASE WHEN e.genero = 'M' THEN 1 ELSE 0 END) AS estudiantes_varones,
    SUM(CASE WHEN e.genero = 'F' THEN 1 ELSE 0 END) AS estudiantes_hembras
FROM Matricula m
JOIN Estudiante e ON e.cedula_estudiante = m.cedula_estudiante
WHERE m.estado_matricula = 'activa'
GROUP BY m.codigo_seccion;

CREATE VIEW vw_Evaluacion_Resultado AS
SELECT
    ev.id_evaluacion,
    ev.nota,
    pe.tipo_evaluacion,
    CASE
        WHEN ev.nota IS NULL THEN 'P'
        WHEN pe.tipo_evaluacion = 'L' THEN
            CASE WHEN ev.nota = 1 THEN 'A' ELSE 'R' END
        WHEN ev.nota >= CAST((SELECT valor FROM Parametro_Sistema WHERE clave = 'nota_minima_aprobatoria') AS FLOAT)
            THEN 'A'
        ELSE 'R'
    END AS resultado
FROM Evaluacion ev
JOIN Plan_Estudios pe
  ON pe.siglas_materia = ev.siglas_materia
 AND pe.id_mencion     = ev.id_mencion
 AND pe.codigo_grado   = ev.codigo_grado
 AND pe.codigo_ano_escolar = ev.codigo_ano_escolar;
