<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    /* Página landscape para el resumen de sección */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 7.5pt; color: #000; }

    .header { border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 7px; }
    .header-inner { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
    .header-logo, .header-logo-r { width: 50px; height: 50px; object-fit: contain; flex-shrink: 0; }
    .header-text { text-align: center; flex: 1; line-height: 1.35; }
    .header-text .rep { font-size: 6.5pt; font-weight: bold; text-transform: uppercase; }
    .header-text h1   { font-size: 8pt; text-transform: uppercase; font-weight: bold; }
    .header-text .cod { font-size: 6.5pt; }

    .doc-title { text-align: center; font-size: 10pt; font-weight: bold; text-transform: uppercase;
                 margin: 5px 0 3px; letter-spacing: 1px; }
    .subtitulo { text-align: center; font-size: 8pt; margin-bottom: 6px; }

    /* TABLA PRINCIPAL — Libro de calificaciones */
    table.resumen {
        width: 100%;
        border-collapse: collapse;
        font-size: 7pt;
    }
    table.resumen thead tr.fila-titulo-materia th {
        background: #1a1a2e;
        color: #fff;
        border: 1px solid #000;
        padding: 3px 2px;
        font-size: 6.5pt;
        text-align: center;
        vertical-align: bottom;
    }
    table.resumen thead tr.fila-titulo-materia th.col-n {
        width: 20px;
    }
    table.resumen thead tr.fila-titulo-materia th.col-cedula {
        width: 70px;
    }
    table.resumen thead tr.fila-titulo-materia th.col-nombre {
        text-align: left;
        width: 140px;
        padding-left: 3px;
    }
    table.resumen thead tr.fila-momentos th {
        background: #2c3e7a;
        color: #fff;
        border: 1px solid #444;
        padding: 2px 1px;
        font-size: 6pt;
        text-align: center;
    }
    /* Celdas de datos */
    table.resumen tbody td {
        border: 1px solid #999;
        padding: 2px 1px;
        text-align: center;
        vertical-align: middle;
    }
    table.resumen tbody td.nombre-cell {
        text-align: left;
        padding-left: 3px;
    }
    table.resumen tbody tr:nth-child(even) td { background: #f4f4f4; }
    .nota-baja { color: #b71c1c; font-weight: bold; }
    .nota-alta { color: #1b5e20; }
    .res-A { color: #1b5e20; font-weight: bold; }
    .res-R { color: #b71c1c; font-weight: bold; }

    /* RESUMEN FINAL */
    .resumen-pie {
        display: flex; gap: 12px; margin-top: 8px; align-items: flex-start;
    }
    .conteo-box { display: flex; gap: 8px; }
    .conteo-item { text-align: center; padding: 3px 10px; border: 1px solid #333; }
    .conteo-label { font-size: 6pt; font-weight: bold; text-transform: uppercase; color: #444; }
    .conteo-valor { font-size: 12pt; font-weight: bold; }

    /* FIRMAS */
    .firmas { display: flex; justify-content: space-around; margin-top: 24px; }
    .firma-bloque { text-align: center; width: 30%; }
    .firma-linea { border-top: 1px solid #000; margin-bottom: 3px; }
    .firma-nombre { font-weight: bold; font-size: 7.5pt; text-transform: uppercase; }
    .firma-cargo  { font-size: 7pt; font-style: italic; }
    .footer { text-align: center; font-size: 6.5pt; color: #666; margin-top: 8px;
              border-top: 1px solid #ccc; padding-top: 3px; }
</style>
</head>
<body>

<!-- ENCABEZADO GESMAN/MPPE -->
<div class="header">
    <div class="header-inner">
        @php
            $logoPath   = public_path('imagenes/logo_izq.png');
            $escudoPath = public_path('imagenes/logo_der.png');
            $logoSGAE   = public_path('imagenes/SGAE.png');
        @endphp
        @if(file_exists($logoPath))
            <img class="header-logo" src="{{ $logoPath }}" alt="Logo">
        @elseif(file_exists($logoSGAE))
            <img class="header-logo" src="{{ $logoSGAE }}" alt="Logo">
        @else
            <div style="width:50px;"></div>
        @endif

        <div class="header-text">
            <p class="rep">República Bolivariana de Venezuela — Min. Poder Popular para la Educación</p>
            @if($institucion?->estado)<p class="rep">Estado Bolivariano de {{ $institucion->estado }}</p>@endif
            <h1>{{ $institucion->nombre ?? 'INSTITUCIÓN EDUCATIVA' }}</h1>
            <p class="cod">Cód. DEA: {{ $institucion->codigo_dea ?? '' }}
                @if($institucion?->zona_educativa) | Zona: {{ $institucion->zona_educativa }} @endif
                @if($institucion?->telefono) | Tel: {{ $institucion->telefono }} @endif
            </p>
        </div>

        @if(file_exists($escudoPath))
            <img class="header-logo-r" src="{{ $escudoPath }}" alt="Escudo">
        @else
            <div style="width:50px;"></div>
        @endif
    </div>
</div>

<p class="doc-title">Resumen de Calificaciones por Sección</p>
<p class="subtitulo">
    <strong>{{ $seccion->grado->nombre ?? '' }}</strong> — Sección <strong>{{ $seccion->letra }}</strong>
    @if($seccion->mencion) | Mención <strong>{{ $seccion->mencion->nombre }}</strong> @endif
    | Turno: {{ ucfirst($seccion->turno ?? '') }}
    | Año Escolar: <strong>{{ $anio->codigo_ano_escolar }}</strong>
    | Docente Guía: {{ $seccion->docenteGuia?->personal?->nombre_completo ?? '—' }}
    &nbsp;|&nbsp;
    @if($numero_momento)
        <strong>{{ $numero_momento }}° Momento Evaluativo</strong>
    @else
        <strong>Evaluación Final</strong>
    @endif
</p>

<!-- TABLA PRINCIPAL -->
<table class="resumen">
    <thead>
        <!-- Fila 1: Columnas base + nombres de materias -->
        <tr class="fila-titulo-materia">
            <th class="col-n" rowspan="2">#</th>
            <th class="col-cedula" rowspan="2">Cédula</th>
            <th class="col-nombre" rowspan="2" style="text-align:left;padding-left:3px">Apellidos y Nombres</th>
            @foreach($materias as $mat)
                @php
                    $colSpan = 0;
                    if (!$numero_momento || $numero_momento >= 1) $colSpan++;
                    if (!$numero_momento || $numero_momento >= 2) $colSpan++;
                    if (!$numero_momento || $numero_momento >= 3) $colSpan++;
                    $colSpan += 2; // definitiva + resultado
                @endphp
                <th colspan="{{ $colSpan }}" style="font-size:6pt;writing-mode:horizontal-tb;max-width:60px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;" title="{{ $mat->nombre }}">
                    {{ $mat->siglas }}
                </th>
            @endforeach
            <th rowspan="2" style="width:28px;">Prom.</th>
            <th rowspan="2" style="width:22px;">Cond.</th>
        </tr>
        <!-- Fila 2: Sub-encabezado de momentos por materia -->
        <tr class="fila-momentos">
            @foreach($materias as $mat)
                @if(!$numero_momento || $numero_momento >= 1)<th style="width:18px;">M1</th>@endif
                @if(!$numero_momento || $numero_momento >= 2)<th style="width:18px;">M2</th>@endif
                @if(!$numero_momento || $numero_momento >= 3)<th style="width:18px;">M3</th>@endif
                <th style="width:20px;">Def.</th>
                <th style="width:18px;">Res.</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
    @foreach($estudiantesData as $idx => $ed)
        @php
            $sumaProm = 0; $cntProm = 0;
            $todoAprobado = true; $tieneDatos = false;
        @endphp
        <tr>
            <td>{{ $ed['numero_lista'] ?? ($idx + 1) }}</td>
            <td>{{ $ed['tipo_doc'] ?? 'V' }}-{{ $ed['cedula'] }}</td>
            <td class="nombre-cell">{{ strtoupper($ed['apellidos']) }}, {{ $ed['nombres'] }}</td>
            @foreach($materias as $mat)
                @php
                    $siglas = $mat->siglas;
                    $evsMat = $ed['evaluaciones']->get($siglas, collect());
                    $n1 = optional($evsMat->firstWhere('numero_momento', 1))->nota;
                    $n2 = optional($evsMat->firstWhere('numero_momento', 2))->nota;
                    $n3 = optional($evsMat->firstWhere('numero_momento', 3))->nota;
                    $vals = array_filter([$n1, $n2, $n3], fn($v) => $v !== null);
                    $def = count($vals) ? round(array_sum($vals) / count($vals), 2) : null;
                    $tipoEval = $mat->tipo_evaluacion ?? 'N';
                    if ($tipoEval === 'L') {
                        $res = $def !== null ? ($def == 1 ? 'A' : 'R') : '—';
                    } else {
                        $res = $def !== null ? ($def >= $nota_minima ? 'A' : 'R') : '—';
                    }
                    if ($def !== null) { $sumaProm += $def; $cntProm++; $tieneDatos = true; }
                    if ($res === 'R') $todoAprobado = false;
                @endphp
                @if(!$numero_momento || $numero_momento >= 1)
                    <td class="{{ $n1 !== null && $tipoEval !== 'L' && $n1 < $nota_minima ? 'nota-baja' : '' }}">{{ $n1 ?? '—' }}</td>
                @endif
                @if(!$numero_momento || $numero_momento >= 2)
                    <td class="{{ $n2 !== null && $tipoEval !== 'L' && $n2 < $nota_minima ? 'nota-baja' : '' }}">{{ $n2 ?? '—' }}</td>
                @endif
                @if(!$numero_momento || $numero_momento >= 3)
                    <td class="{{ $n3 !== null && $tipoEval !== 'L' && $n3 < $nota_minima ? 'nota-baja' : '' }}">{{ $n3 ?? '—' }}</td>
                @endif
                <td class="{{ $def !== null && $tipoEval !== 'L' && $def < $nota_minima ? 'nota-baja' : '' }}">{{ $def ?? '—' }}</td>
                <td class="res-{{ $res }}">{{ $res }}</td>
            @endforeach
            @php
                $promFinal = $cntProm ? round($sumaProm / $cntProm, 2) : null;
                $condFinal = !$tieneDatos ? '—' : ($todoAprobado ? 'A' : 'R');
            @endphp
            <td class="{{ $promFinal !== null && $promFinal < $nota_minima ? 'nota-baja' : '' }}">
                {{ $promFinal ?? '—' }}
            </td>
            <td class="res-{{ $condFinal }}"><strong>{{ $condFinal }}</strong></td>
        </tr>
    @endforeach
    </tbody>
</table>

<!-- CONTEO Y RESUMEN PIE -->
<div class="resumen-pie">
    <div class="conteo-box">
        @if(isset($conteoVista))
        <div class="conteo-item">
            <p class="conteo-label">Varones</p>
            <p class="conteo-valor">{{ $conteoVista->estudiantes_varones ?? '—' }}</p>
        </div>
        <div class="conteo-item">
            <p class="conteo-label">Hembras</p>
            <p class="conteo-valor">{{ $conteoVista->estudiantes_hembras ?? '—' }}</p>
        </div>
        <div class="conteo-item" style="background:#1a1a2e;color:#fff;border-color:#1a1a2e;">
            <p class="conteo-label" style="color:#ccc;">Total</p>
            <p class="conteo-valor">{{ $conteoVista->total_estudiantes ?? count($estudiantesData) }}</p>
        </div>
        @else
        <div class="conteo-item" style="background:#1a1a2e;color:#fff;border-color:#1a1a2e;">
            <p class="conteo-label" style="color:#ccc;">Total</p>
            <p class="conteo-valor">{{ count($estudiantesData) }}</p>
        </div>
        @endif
    </div>
    <div style="font-size:7pt;margin-left:12px;">
        <strong>Nota Mínima Aprobatoria:</strong> {{ $nota_minima }} &nbsp;|&nbsp;
        <strong>Fecha:</strong> {{ $fecha_hoy }}
    </div>
</div>

<!-- FIRMAS -->
<div class="firmas">
    <div class="firma-bloque">
        <br>
        <div class="firma-linea">&nbsp;</div>
        <p class="firma-nombre">{{ strtoupper($institucion->director?->nombre_completo ?? 'DIRECTOR(A)') }}</p>
        <p class="firma-cargo">Director(a) del Plantel</p>
    </div>
    <div class="firma-bloque">
        <br>
        <div class="firma-linea">&nbsp;</div>
        <p class="firma-nombre">{{ strtoupper($seccion->docenteGuia?->personal?->nombre_completo ?? 'DOCENTE GUÍA') }}</p>
        <p class="firma-cargo">Docente Guía de la Sección</p>
    </div>
</div>

<div class="footer">
    Resumen generado el {{ $fecha_hoy }} — SGAE Sistema de Gestión Académica Escolar
</div>
</body>
</html>
