<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inscripciones</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica', sans-serif;
            color: #334155;
            font-size: 12px;
            line-height: 1.5;
        }
        /* Encabezado Estilo SGAE */
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #0ea5e9;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #0ea5e9;
            text-transform: uppercase;
            margin: 0;
            font-size: 22px;
            letter-spacing: -1px;
        }
        .header p {
            margin: 5px 0;
            color: #64748b;
            font-weight: bold;
        }

        /* Información del Reporte */
        .info-box {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-box td {
            vertical-align: top;
        }
        .filter-badge {
            background-color: #f0f9ff;
            color: #0369a1;
            padding: 5px 10px;
            border-radius: 8px;
            border: 1px solid #bae6fd;
            font-size: 10px;
            text-transform: uppercase;
        }

        /* Tabla de Datos */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f8fafc;
            color: #0369a1;
            text-transform: uppercase;
            font-size: 10px;
            padding: 12px 8px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .cedula {
            font-family: monospace;
            color: #94a3b8;
            font-size: 10px;
        }
        .nombre-estudiante {
            font-weight: bold;
            color: #1e293b;
        }
        .materia {
            color: #475569;
        }
        .status-pill {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .status-activa { background-color: #dcfce7; color: #166534; }
        .status-inactiva { background-color: #fee2e2; color: #991b1b; }

        /* Pie de página */
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>SGAE - Sistema de Gestión Académica</h1>
        <p>{{ $titulo }}</p>
    </div>

    <table class="info-box">
        <tr>
            <td>
                <strong>Fecha de emisión:</strong> {{ $fecha }}<br>
                <strong>Periodo:</strong> General
            </td>
            <td class="text-right">
                <span class="filter-badge">Grado: {{ $filtros['grado'] }}</span>
                <span class="filter-badge">Sección: {{ $filtros['seccion'] }}</span>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="35%">Estudiante</th>
                <th width="30%">Materia</th>
                <th class="text-center">Periodo</th>
                <th class="text-center">Grado/Secc</th>
                <th class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inscripciones as $ins)
            <tr>
                <td>
                    <span class="cedula">{{ $ins->estudiante->cedula }}</span><br>
                    <span class="nombre-estudiante">{{ $ins->estudiante->apellidos }}, {{ $ins->estudiante->nombres }}</span>
                </td>
                <td class="materia">{{ $ins->materia->nombre }}</td>
                <td class="text-center">{{ $ins->periodo }}</td>
                <td class="text-center">
                    {{ $ins->estudiante->grado }} - {{ $ins->estudiante->seccion }}
                </td>
                <td class="text-center">
                    <span class="status-pill {{ str_contains(strtolower($ins->estado), 'acti') ? 'status-activa' : 'status-inactiva' }}">
                        {{ $ins->estado }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Este documento es un reporte oficial generado por el Sistema de Gestión Académica (SGAE). 
        Página 1 de 1
    </div>

</body>
</html>