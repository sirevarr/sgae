<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 9pt; color: #000; }
    .header { border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 8px; }
    .header-inner { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .header-logo, .header-logo-r { width: 58px; height: 58px; object-fit: contain; flex-shrink: 0; }
    .header-text { text-align: center; flex: 1; line-height: 1.4; }
    .header-text .rep { font-size: 7pt; font-weight: bold; text-transform: uppercase; }
    .header-text h1   { font-size: 9pt; text-transform: uppercase; font-weight: bold; }
    .header-text .cod { font-size: 7pt; }
    .doc-title { text-align: center; font-size: 11pt; font-weight: bold; text-transform: uppercase; margin: 6px 0 5px; }
    .seccion-info { display: flex; gap: 0; margin-bottom: 6px; border: 1.5px solid #000; }
    .si-item { flex: 1; padding: 4px 6px; border-right: 1px solid #888; }
    .si-item:last-child { border-right: none; }
    .si-label { font-weight: bold; font-size: 6.5pt; text-transform: uppercase; color: #444; }
    .si-val { font-size: 8.5pt; }

    table.lista { width: 100%; border-collapse: collapse; font-size: 8pt; }
    table.lista thead tr { background: #1a1a2e; color: #fff; }
    table.lista th { padding: 4px 3px; text-align: center; border: 1px solid #000; font-size: 7.5pt; font-weight: bold; }
    table.lista td { padding: 3px 3px; border: 1px solid #333; text-align: center; }
    table.lista .nombre-col { text-align: left; padding-left: 4px; }
    table.lista tr:nth-child(even) td { background: #f0f0f0; }

    .totales-row { background: #e8e8e8 !important; font-weight: bold; }
    .totales-row td { border-top: 2px solid #000 !important; font-size: 8pt; }

    .conteo-box { display: flex; gap: 16px; margin-top: 8px; justify-content: flex-end; font-size: 8pt; }
    .conteo-item { text-align: center; padding: 4px 12px; border: 1px solid #333; }
    .conteo-label { font-size: 7pt; font-weight: bold; text-transform: uppercase; color: #444; }
    .conteo-valor { font-size: 12pt; font-weight: bold; }

    .firma-area { display: flex; justify-content: space-around; margin-top: 28px; }
    .firma-bloque { text-align: center; width: 38%; }
    .firma-linea { border-top: 1px solid #000; margin-bottom: 3px; }
    .firma-nombre { font-weight: bold; font-size: 7.5pt; text-transform: uppercase; }
    .footer { text-align: center; font-size: 7pt; color: #555; margin-top: 10px;
              border-top: 1px solid #ccc; padding-top: 4px; }
</style>
</head>
<body>

<div class="header">
    <div class="header-inner">
        @php
            $logoPath  = public_path('imagenes/logo_izq.png');
            $escudoPath = public_path('imagenes/logo_der.png');
            $logoSGAE  = public_path('imagenes/SGAE.png');
        @endphp
        @if(file_exists($logoPath))
            <img class="header-logo" src="{{ $logoPath }}" alt="Logo">
        @elseif(file_exists($logoSGAE))
            <img class="header-logo" src="{{ $logoSGAE }}" alt="Logo">
        @else
            <div style="width:58px;"></div>
        @endif

        <div class="header-text">
            <p class="rep">República Bolivariana de Venezuela — Ministerio del Poder Popular para la Educación</p>
            @if($institucion && $institucion->estado)
            <p class="rep">Estado Bolivariano de {{ $institucion->estado }}</p>
            @endif
            <h1>{{ $institucion->nombre ?? 'INSTITUCIÓN EDUCATIVA' }}</h1>
            <p class="cod">Cód. DEA: {{ $institucion->codigo_dea ?? '' }}
                @if($institucion?->zona_educativa) &nbsp;|&nbsp; Zona Educativa: {{ $institucion->zona_educativa }} @endif
                @if($institucion?->telefono) &nbsp;|&nbsp; Tel: {{ $institucion->telefono }} @endif
            </p>
        </div>

        @if(file_exists($escudoPath))
            <img class="header-logo-r" src="{{ $escudoPath }}" alt="Escudo">
        @else
            <div style="width:58px;"></div>
        @endif
    </div>
</div>

<p class="doc-title">Lista de Sección</p>

<div class="seccion-info">
    <div class="si-item">
        <p class="si-label">Grado</p>
        <p class="si-val"><strong>{{ $seccion->grado->nombre ?? '' }}</strong></p>
    </div>
    <div class="si-item">
        <p class="si-label">Sección</p>
        <p class="si-val"><strong>{{ $seccion->letra }}</strong></p>
    </div>
    <div class="si-item">
        <p class="si-label">Turno</p>
        <p class="si-val">{{ ucfirst($seccion->turno ?? '') }}</p>
    </div>
    <div class="si-item">
        <p class="si-label">Aula</p>
        <p class="si-val">{{ $seccion->aula_asignada ?? '—' }}</p>
    </div>
    <div class="si-item">
        <p class="si-label">Mención</p>
        <p class="si-val">{{ $seccion->mencion->nombre ?? '—' }}</p>
    </div>
    <div class="si-item">
        <p class="si-label">Docente Guía</p>
        <p class="si-val">{{ $seccion->docenteGuia?->personal?->nombre_completo ?? '—' }}</p>
    </div>
    <div class="si-item">
        <p class="si-label">Año Escolar</p>
        <p class="si-val"><strong>{{ $anio->codigo_ano_escolar }}</strong></p>
    </div>
</div>

<table class="lista">
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:13%">Cédula</th>
            <th style="width:33%">Apellidos y Nombres</th>
            <th style="width:5%">Gén.</th>
            <th style="width:9%">F. Nacimiento</th>
            <th style="width:10%">Teléfono</th>
            <th style="width:11%">Cond. Ingreso</th>
            <th style="width:15%">Observaciones</th>
        </tr>
    </thead>
    <tbody>
    @php $varones = 0; $hembras = 0; @endphp
    @foreach($matriculas as $m)
        @php
            $genero = $m->estudiante->genero ?? '';
            if ($genero === 'M') $varones++;
            elseif ($genero === 'F') $hembras++;
        @endphp
        <tr>
            <td>{{ $m->numero_lista ?? $loop->iteration }}</td>
            <td>{{ $m->estudiante->tipo_documento ?? 'V' }}-{{ $m->estudiante->cedula_estudiante }}</td>
            <td class="nombre-col">{{ strtoupper($m->estudiante->apellidos) }}, {{ $m->estudiante->nombres }}</td>
            <td>{{ $genero === 'M' ? 'M' : ($genero === 'F' ? 'F' : '—') }}</td>
            <td>{{ $m->estudiante->fecha_nacimiento ? \Carbon\Carbon::parse($m->estudiante->fecha_nacimiento)->format('d/m/Y') : '—' }}</td>
            <td>{{ $m->estudiante->telefono ?? '—' }}</td>
            <td>{{ ucfirst($m->condicion_ingreso ?? '—') }}</td>
            <td>{{ $m->observaciones ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<!-- CONTEO USANDO vw_Seccion_Conteo -->
@php
    $totalLista = $matriculas->count();
    // Usar datos de la vista si están disponibles, sino calcular desde los datos locales
    $varonesFinal = isset($conteoVista) ? ($conteoVista->estudiantes_varones ?? $varones) : $varones;
    $hembrasFinal  = isset($conteoVista) ? ($conteoVista->estudiantes_hembras  ?? $hembras)  : $hembras;
    $totalFinal    = isset($conteoVista) ? ($conteoVista->total_estudiantes     ?? $totalLista) : $totalLista;
@endphp

<div class="conteo-box">
    <div class="conteo-item">
        <p class="conteo-label">Varones</p>
        <p class="conteo-valor">{{ $varonesFinal }}</p>
    </div>
    <div class="conteo-item">
        <p class="conteo-label">Hembras</p>
        <p class="conteo-valor">{{ $hembrasFinal }}</p>
    </div>
    <div class="conteo-item" style="background:#1a1a2e;color:#fff;border-color:#1a1a2e;">
        <p class="conteo-label" style="color:#ccc;">Total</p>
        <p class="conteo-valor">{{ $totalFinal }}</p>
    </div>
</div>

<div class="firma-area">
    <div class="firma-bloque">
        <br>
        <div class="firma-linea">&nbsp;</div>
        <p class="firma-nombre">{{ strtoupper($institucion->director?->nombre_completo ?? 'DIRECTOR(A)') }}</p>
        <p style="font-size:7pt;font-style:italic;">Director(a) del Plantel</p>
    </div>
    <div class="firma-bloque">
        <br>
        <div class="firma-linea">&nbsp;</div>
        <p class="firma-nombre">{{ strtoupper($seccion->docenteGuia?->personal?->nombre_completo ?? 'DOCENTE GUÍA') }}</p>
        <p style="font-size:7pt;font-style:italic;">Docente Guía</p>
    </div>
</div>

<div class="footer">
    Lista impresa el {{ $fecha_hoy }} — SGAE Sistema de Gestión Académica Escolar
</div>
</body>
</html>
