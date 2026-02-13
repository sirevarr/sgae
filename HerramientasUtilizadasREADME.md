Para que el proyecto funcione correctamente, se utilizaron las siguientes tecnologías obligatorias:
• Backend: Laravel 10 (PHP 8.1+).
• Frontend: Vue.js 3.
• Comunicación: Inertia.js (conecta el servidor con la vista enviando datos como "props").
• Estilos: Tailwind CSS.
• Base de Datos: MySQL (Relacional) configurada en el puerto 3306.
• Entorno de Desarrollo: Laragon (proporciona Apache, MySQL y el intérprete de PHP necesarios).
• Entorno de Ejecución Frontend: Node.js (obligatorio para compilar los activos de Vue 3 y Tailwind CSS).
• Herramienta de Construcción: Vite ^5.0.

Siga estos pasos para ejecutar el proyecto en su entorno local:
Clonar el repositorio:
Instalar dependencias de PHP:
Instalar dependencias de Node.js:
Configurar el entorno (.env):
    ◦ Copie el archivo .env.example y renómbrelo a .env.
    ◦ Asegúrese de configurar el nombre de la base de datos como sgae_db.
    ◦ El puerto predeterminado de MySQL es 3306.
Generar la clave de la aplicación:
Ejecutar migraciones:
(Esto creará automáticamente las tablas necesarias: estudiantes, materias, inscripciones, evaluaciones y usuarios).

Es necesario abrir dos terminales diferentes apuntando a la carpeta del proyecto:
• Terminal 1 (Backend):
• La aplicación estará disponible en http://127.0.0.1:8000.
• Terminal 2 (Frontend):
• Para compilar los activos de Vue 3 y Tailwind CSS en tiempo real.

El sistema cumple con los 4 módulos funcionales requeridos:
Estudiantes: Registro, edición y filtrado por grado/sección.
Materias: Gestión de asignaturas y créditos académicos.
Inscripciones: Relación histórica entre alumnos y materias por período.
Evaluaciones: Carga de notas con cálculo automático de promedio y estado (Aprobado/Reprobado).
Autenticación: Sistema de seguridad y control de acceso mediante Login (Laravel Breeze).

• Desarrollador: Desire Varela (C.I: 32.507.544).
• Cátedra: Programación IV - Instituto Universitario de Tecnología “Antonio José de Sucre”. Extensión Charallave