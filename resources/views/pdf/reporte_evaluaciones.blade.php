<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Evaluaciones</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; }
        .info { margin-bottom: 20px; font-weight: bold; color: #0369a1; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f0f9ff; color: #0369a1; padding: 10px; border: 1px solid #e0f2fe; text-transform: uppercase; font-size: 10px; }
        td { padding: 8px; border: 1px solid #f1f5f9; text-align: center; }
        .student-info { text-align: left; font-weight: bold; }
        .aprobado { color: #15803d; font-weight: bold; }
        .reprobado { color: #b91c1c; font-weight: bold; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin:0; color: #0ea5e9;">SISTEMA ACADÉMICO</h1>
        <p style="margin:5px 0;">{{ $titulo }}</p>
    </div>

    <div class="info">
        Fecha: {{ $fecha }} | Grado: {{ $filtros['grado'] }} | Estado: {{ $filtros['estado'] }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Estudiante</th>
                <th style="width: 20%;">Materia</th>
                <th>P1</th>
                <th>P2</th>
                <th>Final</th>
                <th>Promedio</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($evaluaciones as $eva)
            <tr>
                <td class="student-info">
                    {{ $eva->inscripcion->estudiante->apellidos }}, {{ $eva->inscripcion->estudiante->nombres }}
                    <br><small style="color:#64748b;">CI: {{ $eva->inscripcion->estudiante->cedula }}</small>
                </td>
                <td>{{ $eva->inscripcion->materia->nombre }}</td>
                <td>{{ $eva->nota_parcial1 }}</td>
                <td>{{ $eva->nota_parcial2 }}</td>
                <td>{{ $eva->nota_final }}</td>
                <td style="font-weight:bold;">{{ $eva->promedio }}</td>
                <td class="{{ strtolower($eva->estado) }}">{{ strtoupper($eva->estado) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>