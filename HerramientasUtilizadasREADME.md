Para que el proyecto funcione correctamente, se utilizan las siguientes tecnologías:
• Backend: Laravel 10 (PHP 8.1+).
• Frontend: Vue.js 3.
• Comunicación: Inertia.js.
• Estilos: Tailwind CSS.
• Base de Datos: SQL Server, configurada en el puerto 1433.
• Entorno de Ejecución Frontend: Node.js.
• Herramienta de Construcción: Vite ^5.0.

Pasos para ejecutar el proyecto localmente:
1. Clonar el repositorio.
2. Instalar dependencias de PHP.
3. Instalar dependencias de Node.js.
4. Configurar el archivo .env con la conexión a SQL Server.
5. Generar la clave de la aplicación.
6. Ejecutar las migraciones.

La aplicación queda disponible en http://127.0.0.1:8000.

El sistema cumple con los 4 módulos funcionales requeridos:
Estudiantes: Registro, edición y filtrado por grado/sección.
Materias: Gestión de asignaturas y créditos académicos.
Inscripciones: Relación histórica entre alumnos y materias por período.
Evaluaciones: Carga de notas con cálculo automático de promedio y estado (Aprobado/Reprobado).
Autenticación: Sistema de seguridad y control de acceso mediante Login (Laravel Breeze).

• Desarrollador: Desire Varela (C.I: 32.507.544).
• Cátedra: Programación IV - Instituto Universitario de Tecnología “Antonio José de Sucre”. Extensión Charallave