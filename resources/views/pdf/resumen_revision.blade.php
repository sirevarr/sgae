<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; color: #000; }

    /* ── ENCABEZADO INSTITUCIONAL ── */
    .header {
        width: 100%;
        border-bottom: 3px double #000;
        padding-bottom: 8px;
        margin-bottom: 10px;
    }
    .header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .header-logo, .header-logo-r { width: 68px; height: 68px; object-fit: contain; flex-shrink: 0; }
    .header-text { text-align: center; flex: 1; line-height: 1.4; }
    .header-text .rep    { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; }
    .header-text .estado { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; }
    .header-text h1      { font-size: 9.5pt; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px; }
    .header-text .cod    { font-size: 7.5pt; }

    /* ── TÍTULO ── */
    .doc-title { text-align: center; margin: 10px 0 6px; }
    .doc-title h2 {
        font-size: 13pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 2px;
        border-bottom: 2px solid #000;
        display: inline-block;
        padding-bottom: 2px;
    }
    .subtitulo { text-align: center; font-size: 9pt; font-weight: bold; margin-bottom: 8px; color: #333; }

    /* ── DATOS DEL ESTUDIANTE ── */
    .datos-estudiante {
        display: flex;
        gap: 0;
        margin-bottom: 10px;
        border: 1.5px solid #222;
    }
    .dato-bloque {
        flex: 1;
        padding: 5px 8px;
        border-right: 1px solid #999;
    }
    .dato-bloque:last-child { border-right: none; }
    .dato-etiqueta {
        font-weight: bold;
        font-size: 7pt;
        text-transform: uppercase;
        color: #444;
        margin-bottom: 1px;
    }
    .dato-valor {
        font-size: 9.5pt;
        border-bottom: 1px solid #bbb;
        margin-bottom: 3px;
        padding-bottom: 1px;
        min-height: 14px;
    }

    /* ── TABLA DE MATERIAS PENDIENTES ── */
    table.pendientes {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
        font-size: 9pt;
    }
    table.pendientes thead tr {
        background: #8B2500;
        color: #fff;
    }
    table.pendientes th {
        padding: 6px 5px;
        text-align: center;
        border: 1px solid #6a1a00;
        font-size: 8pt;
        font-weight: bold;
    }
    table.pendientes th.col-materia { text-align: left; padding-left: 8px; }
    table.pendientes td {
        padding: 5px 4px;
        border: 1px solid #ccc;
        text-align: center;
    }
    table.pendientes td.materia-nombre { text-align: left; padding-left: 8px; font-size: 8.5pt; }
    table.pendientes tr:nth-child(even) td { background: #fdf5f0; }

    /* ── ESTADOS ── */
    .estado-pendiente  { color: #b71c1c; font-weight: bold; background: #fff3e0 !important; }
    .estado-aprobada   { color: #1b5e20; font-weight: bold; }
    .estado-no_aprobada { color: #c65a00; font-weight: bold; }
    .estado-badge {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 7.5pt;
        font-weight: bold;
    }
    .badge-pendiente   { background: #ffebee; color: #b71c1c; border: 1px solid #ef9a9a; }
    .badge-aprobada    { background: #e8f5e9; color: #1b5e20; border: 1px solid #a5d6a7; }
    .badge-no_aprobada { background: #fff3e0; color: #c65a00; border: 1px solid #ffcc80; }

    /* ── RESUMEN PIE ── */
    .resumen-conteo {
        margin-top: 10px;
        border: 1.5px solid #000;
        padding: 6px 12px;
        display: flex;
        justify-content: space-between;
        font-size: 9pt;
        background: #fafafa;
    }
    .resumen-item { text-align: center; }
    .resumen-label { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; color: #444; }
    .resumen-valor { font-size: 14pt; font-weight: bold; }
    .rv-pendiente  { color: #b71c1c; }
    .rv-aprobada   { color: #1b5e20; }
    .rv-no_aprobada { color: #c65a00; }

    /* ── LEYENDA ── */
    .leyenda {
        margin-top: 8px;
        font-size: 7.5pt;
        color: #555;
        border-top: 1px dashed #ccc;
        padding-top: 5px;
    }

    /* ── FIRMAS ── */
    .firmas { display: flex; justify-content: space-around; margin-top: 36px; }
    .firma-bloque { text-align: center; width: 42%; }
    .firma-linea { border-top: 1px solid #000; margin-bottom: 3px; }
    .firma-nombre { font-weight: bold; font-size: 8.5pt; text-transform: uppercase; }
    .firma-cargo  { font-size: 7.5pt; font-style: italic; }

    /* ── PIE ── */
    .footer {
        text-align: center;
        font-size: 7pt;
        color: #555;
        margin-top: 10px;
        border-top: 1px solid #ccc;
        padding-top: 3px;
    }
</style>
</head>
<body>

<!-- ENCABEZADO INSTITUCIONAL -->
<div class="header">
    <div class="header-inner">
        @php
            $logoPath   = public_path('imagenes/logo_izq.png');
            $escudoPath = public_path('imagenes/logo_der.png');
            $logoSGAE   = public_path('imagenes/SGAE.png');
        @endphp
        @if(file_exists($logoPath))
            <img class="header-logo" src="{{ $logoPath }}" alt="Logo Izq">
        @elseif(file_exists($logoSGAE))
            <img class="header-logo" src="{{ $logoSGAE }}" alt="Logo">
        @else
            <div style="width:68px;"></div>
        @endif

        <div class="header-text">
            <p class="rep">República Bolivariana de Venezuela</p>
            @if($institucion && $institucion->estado)
            <p class="estado">Estado Bolivariano de {{ $institucion->estado }}</p>
            @endif
            <h1>{{ $institucion->nombre ?? 'INSTITUCIÓN EDUCATIVA' }}</h1>
            <p class="cod">Código DEA: {{ $institucion->codigo_dea ?? '' }}
                @if($institucion && $institucion->zona_educativa)
                &nbsp;|&nbsp; Zona Educativa: {{ $institucion->zona_educativa }}
                @endif
            </p>
            @if($institucion && $institucion->municipio)
            <p class="cod">{{ $institucion->municipio }}{{ $institucion->estado ? ' — ' . $institucion->estado : '' }}</p>
            @endif
        </div>

        @if(file_exists($escudoPath))
            <img class="header-logo-r" src="{{ $escudoPath }}" alt="Logo Der">
        @else
            <div style="width:68px;"></div>
        @endif
    </div>
</div>

<!-- TÍTULO -->
<div class="doc-title">
    <h2>Resumen de Materias en Proceso de Revisión</h2>
</div>
<p class="subtitulo">
    Año Escolar de Origen: <strong>{{ $codigo_ano_escolar_origen }}</strong>
    &nbsp;|&nbsp; Generado: <strong>{{ $fecha_hoy }}</strong>
</p>

<!-- DATOS DEL ESTUDIANTE -->
<div class="datos-estudiante">
    <div class="dato-bloque" style="flex:2">
        <p class="dato-etiqueta">Apellidos y Nombres</p>
        <p class="dato-valor">
            <strong>{{ strtoupper($estudiante->apellidos) }}, {{ strtoupper($estudiante->nombres) }}</strong>
        </p>
        <p class="dato-etiqueta">Cédula</p>
        <p class="dato-valor">{{ $estudiante->tipo_documento ?? 'V' }}-{{ $estudiante->cedula_estudiante }}</p>
    </div>
    <div class="dato-bloque">
        <p class="dato-etiqueta">Género</p>
        <p class="dato-valor">{{ ucfirst($estudiante->genero ?? '—') }}</p>
        <p class="dato-etiqueta">Fecha de Nacimiento</p>
        <p class="dato-valor">{{ $estudiante->fecha_nacimiento ? \Carbon\Carbon::parse($estudiante->fecha_nacimiento)->format('d/m/Y') : '—' }}</p>
    </div>
    <div class="dato-bloque">
        <p class="dato-etiqueta">Total de Materias Pendientes</p>
        <p class="dato-valor" style="font-size:13pt;font-weight:bold;color:#8B2500;">{{ $pendientes->count() }}</p>
    </div>
</div>

<!-- TABLA DE MATERIAS PENDIENTES -->
@if($pendientes->count() > 0)
<table class="pendientes">
    <thead>
        <tr>
            <th class="col-materia" style="width:35%">Materia / Área de Formación</th>
            <th style="width:12%">Grado</th>
            <th style="width:13%">Año Escolar Origen</th>
            <th style="width:13%">Estado</th>
            <th style="width:12%">Nota Final</th>
            <th style="width:15%">Fecha de Resolución</th>
        </tr>
    </thead>
    <tbody>
    @foreach($pendientes as $pend)
        <tr>
            <td class="materia-nombre">{{ $pend->materia->nombre ?? $pend->siglas_materia }}</td>
            <td>{{ $pend->grado->nombre ?? $pend->codigo_grado }}</td>
            <td>{{ $pend->codigo_ano_escolar_origen }}</td>
            <td>
                @php $estadoKey = $pend->estado ?? 'pendiente'; @endphp
                <span class="estado-badge badge-{{ $estadoKey }}">
                    @if($estadoKey === 'pendiente') Pendiente
                    @elseif($estadoKey === 'aprobada') Aprobada
                    @else No Aprobada
                    @endif
                </span>
            </td>
            <td class="{{ $pend->nota_final !== null && $pend->nota_final >= 10 ? 'estado-aprobada' : ($pend->nota_final !== null ? 'estado-no_aprobada' : '') }}">
                {{ $pend->nota_final !== null ? number_format($pend->nota_final, 2) : '—' }}
            </td>
            <td>{{ $pend->fecha_resolucion ? \Carbon\Carbon::parse($pend->fecha_resolucion)->format('d/m/Y') : '—' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
<div style="text-align:center;padding:20px;border:1px dashed #ccc;margin-top:10px;color:#555;font-size:9pt;">
    No se encontraron materias pendientes para este estudiante en el año escolar indicado.
</div>
@endif

<!-- RESUMEN CONTEO -->
@php
    $cntPendiente   = $pendientes->where('estado', 'pendiente')->count();
    $cntAprobada    = $pendientes->where('estado', 'aprobada')->count();
    $cntNoAprobada  = $pendientes->where('estado', 'no_aprobada')->count();
@endphp
<div class="resumen-conteo">
    <div class="resumen-item">
        <p class="resumen-label">Total Pendientes</p>
        <p class="resumen-valor">{{ $pendientes->count() }}</p>
    </div>
    <div class="resumen-item">
        <p class="resumen-label">En Proceso</p>
        <p class="resumen-valor rv-pendiente">{{ $cntPendiente }}</p>
    </div>
    <div class="resumen-item">
        <p class="resumen-label">Aprobadas</p>
        <p class="resumen-valor rv-aprobada">{{ $cntAprobada }}</p>
    </div>
    <div class="resumen-item">
        <p class="resumen-label">No Aprobadas</p>
        <p class="resumen-valor rv-no_aprobada">{{ $cntNoAprobada }}</p>
    </div>
    <div class="resumen-item">
        <p class="resumen-label">Fecha de Emisión</p>
        <p class="resumen-valor" style="font-size:9pt">{{ $fecha_hoy }}</p>
    </div>
</div>

<!-- LEYENDA -->
<div class="leyenda">
    <strong>Leyenda de estados:</strong>
    <span style="margin-left:8px;">
        <strong style="color:#b71c1c;">Pendiente</strong> = En proceso de revisión / aplazada sin resolver &nbsp;|&nbsp;
        <strong style="color:#1b5e20;">Aprobada</strong> = Materia superada en proceso de revisión &nbsp;|&nbsp;
        <strong style="color:#c65a00;">No Aprobada</strong> = Materia no superada tras revisión
    </span>
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
        <p class="firma-nombre">{{ strtoupper($institucion->coordinador?->nombre_completo ?? 'COORDINADOR(A)') }}</p>
        <p class="firma-cargo">Coordinador(a) Académico(a)</p>
    </div>
</div>

<div class="footer">
    Documento de Revisión Académica — Válido solo con el sello húmedo de la institución.
    Generado por SGAE — {{ $fecha_hoy }}
</div>

</body>
</html>
