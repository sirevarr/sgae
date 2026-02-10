<!DOCTYPE html>
<html>
<head>
    <title>Reporte Académico SGAE</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; }
        .logo { font-size: 24px; font-weight: bold; color: #0ea5e9; margin: 0; }
        .stats-box { background: #f8fafc; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .group-header { background: #0ea5e9; color: white; padding: 8px 15px; border-radius: 5px; margin-top: 25px; text-transform: uppercase; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f1f5f9; color: #475569; text-transform: uppercase; font-size: 10px; padding: 10px; border: 1px solid #e2e8f0; }
        td { padding: 10px; border: 1px solid #e2e8f0; text-align: center; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="logo">SGAE</h1>
        <p style="margin: 0; text-transform: uppercase; letter-spacing: 2px;">Reporte General de Rendimiento</p>
        <p style="margin: 5px 0 0 0; color: #64748b;">Fecha de emisión: {{ $fecha }}</p>
    </div>

    <div class="stats-box">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none; text-align: left;"><strong>Total Estudiantes:</strong> {{ $stats['estudiantesCount'] }}</td>
                <td style="border: none; text-align: left;"><strong>Promedio Global:</strong> {{ $stats['promedioGlobal'] }}</td>
                <td style="border: none; text-align: left;"><strong>% Aprobación:</strong> {{ $stats['porcentajeAprobados'] }}%</td>
            </tr>
        </table>
    </div>

    @foreach($reporteAgrupado as $grupo => $evaluaciones)
        <div class="group-header">Estudiantes de {{ $grupo }}</div>
        <table>
            <thead>
                <tr>
                    <th style="text-align: left; width: 30%;">Estudiante</th>
                    <th>Cédula</th>
                    <th style="text-align: left;">Materia</th>
                    <th>Nota</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($evaluaciones as $eva)
                <tr>
                    <td style="text-align: left; font-weight: bold;">
                        {{ $eva->inscripcion->estudiante->nombres }} {{ $eva->inscripcion->estudiante->apellidos }}
                    </td>
                    <td>{{ $eva->inscripcion->estudiante->cedula }}</td>
                    <td style="text-align: left;">{{ $eva->inscripcion->materia->nombre }}</td>
                    <td style="font-weight: bold; color: {{ $eva->promedio >= 10 ? '#059669' : '#dc2626' }};">
                        {{ number_format($eva->promedio, 2) }}
                    </td>
                    <td>
                        <span style="text-transform: uppercase; font-size: 9px;">{{ $eva->estado }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="footer">Sistema de Control Académico Institucional - SGAE</div>
</body>
</html>