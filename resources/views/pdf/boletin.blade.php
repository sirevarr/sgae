<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; }

    /* ── ENCABEZADO INSTITUCIONAL ── */
    .header { width: 100%; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 12px; }
    .header-inner { display: flex; align-items: center; justify-content: center; gap: 12px; }
    .header-logo { width: 70px; height: 70px; }
    .header-text { text-align: center; }
    .header-text h1 { font-size: 11pt; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; }
    .header-text h2 { font-size: 10pt; text-transform: uppercase; font-weight: bold; }
    .header-text p  { font-size: 9pt; }
    .header-text .ze { font-size: 8pt; font-style: italic; }
    .republic-line { font-size: 9pt; font-weight: bold; text-align: center; margin-bottom: 4px; }

    /* ── TÍTULO DEL DOCUMENTO ── */
    .doc-title { text-align: center; margin: 14px 0 10px; }
    .doc-title h2 { font-size: 14pt; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; border-bottom: 2px solid #000; display: inline-block; padding-bottom: 2px; }
    .tipo-boletin { text-align: center; font-size: 10pt; font-weight: bold; margin-bottom: 8px; }

    /* ── DATOS DEL ESTUDIANTE ── */
    .datos-estudiante { display: flex; gap: 20px; margin-bottom: 10px; border: 1px solid #000; padding: 8px; }
    .dato-bloque { flex: 1; }
    .dato-bloque p { font-size: 9pt; }
    .dato-etiqueta { font-weight: bold; font-size: 8pt; text-transform: uppercase; color: #444; }
    .dato-valor { font-size: 10pt; border-bottom: 1px solid #aaa; margin-bottom: 4px; padding-bottom: 1px; }

    /* ── TABLA DE NOTAS ── */
    table.notas { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 9.5pt; }
    table.notas th { background: #1a1a2e; color: #fff; padding: 5px 4px; text-align: center; border: 1px solid #000; font-size: 8.5pt; }
    table.notas td { padding: 4px 4px; border: 1px solid #000; text-align: center; }
    table.notas tr:nth-child(even) { background: #f5f5f5; }
    table.notas .materia-nombre { text-align: left; padding-left: 6px; }
    table.notas .nota-baja { color: #c0392b; font-weight: bold; }
    table.notas .nota-alta { color: #27ae60; font-weight: bold; }
    .resultado-A { color: #27ae60; font-weight: bold; }
    .resultado-R { color: #c0392b; font-weight: bold; }

    /* ── RESUMEN FINAL ── */
    .resumen { border: 1px solid #000; padding: 8px; margin-top: 10px; display: flex; justify-content: space-between; font-size: 9.5pt; }
    .resumen-item { text-align: center; }
    .resumen-valor { font-size: 14pt; font-weight: bold; }

    /* ── FIRMAS ── */
    .firmas { display: flex; justify-content: space-around; margin-top: 40px; }
    .firma-bloque { text-align: center; width: 40%; }
    .firma-linea { border-top: 1px solid #000; margin-bottom: 4px; }
    .firma-nombre { font-weight: bold; font-size: 9pt; }
    .firma-cargo  { font-size: 8pt; font-style: italic; }

    /* ── PIE ── */
    .footer { text-align: center; font-size: 7.5pt; color: #555; margin-top: 14px; border-top: 1px solid #ccc; padding-top: 4px; }

    .page-break { page-break-after: always; }
</style>
</head>
<body>

<!-- ENCABEZADO INSTITUCIONAL -->
<div class="header">
    <p class="republic-line">REPÚBLICA BOLIVARIANA DE VENEZUELA — MINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN</p>
    <div class="header-inner">
        @if(file_exists(public_path('imagenes/SGAE.png')))
        <img class="header-logo" src="{{ public_path('imagenes/SGAE.png') }}" alt="Logo">
        @endif
        <div class="header-text">
            <h1>{{ $institucion->nombre ?? 'INSTITUCIÓN EDUCATIVA' }}</h1>
            <h2>{{ $institucion->municipio ?? '' }} — {{ $institucion->estado ?? '' }}</h2>
            <p>Zona Educativa: {{ $institucion->zona_educativa ?? 'N/A' }}</p>
            <p class="ze">Código DEA: {{ $institucion->codigo_dea ?? '' }} &nbsp;|&nbsp; Tel: {{ $institucion->telefono ?? '' }}</p>
        </div>
    </div>
</div>

<!-- TÍTULO -->
<div class="doc-title">
    <h2>Boletín de Calificaciones</h2>
</div>
<p class="tipo-boletin">
    Año Escolar: <strong>{{ $anio->codigo_ano_escolar }}</strong>
    &nbsp;|&nbsp;
    @if($numero_momento)
        Momento Evaluativo: <strong>{{ $numero_momento }}°</strong>
    @else
        <strong>Evaluación Final</strong>
    @endif
</p>

<!-- DATOS DEL ESTUDIANTE -->
<div class="datos-estudiante">
    <div class="dato-bloque">
        <p class="dato-etiqueta">Nombres y Apellidos</p>
        <p class="dato-valor">{{ $estudiante->tipo_documento ?? 'V' }}-{{ $estudiante->cedula_estudiante }} &nbsp; {{ strtoupper($estudiante->apellidos) }}, {{ strtoupper($estudiante->nombres) }}</p>
        <p class="dato-etiqueta">Fecha de Nacimiento</p>
        <p class="dato-valor">{{ $estudiante->fecha_nacimiento ? \Carbon\Carbon::parse($estudiante->fecha_nacimiento)->format('d/m/Y') : '—' }}</p>
    </div>
    <div class="dato-bloque">
        <p class="dato-etiqueta">Grado</p>
        <p class="dato-valor">{{ $seccion->grado->nombre ?? '' }} &nbsp; Sección: {{ $seccion->letra }}</p>
        <p class="dato-etiqueta">Mención</p>
        <p class="dato-valor">{{ $seccion->mencion->nombre ?? '—' }}</p>
    </div>
    <div class="dato-bloque">
        <p class="dato-etiqueta">N° de Lista</p>
        <p class="dato-valor">{{ $matricula->numero_lista ?? '—' }}</p>
        <p class="dato-etiqueta">Turno</p>
        <p class="dato-valor">{{ ucfirst($seccion->turno ?? '—') }}</p>
    </div>
</div>

<!-- TABLA DE NOTAS -->
<table class="notas">
    <thead>
        <tr>
            <th style="width:40%">Área de Formación / Materia</th>
            @if(!$numero_momento || $numero_momento >= 1)<th>1er Momento</th>@endif
            @if(!$numero_momento || $numero_momento >= 2)<th>2do Momento</th>@endif
            @if(!$numero_momento || $numero_momento >= 3)<th>3er Momento</th>@endif
            <th>Definitiva</th>
            <th>Resultado</th>
        </tr>
    </thead>
    <tbody>
    @php $sumaNotas = 0; $cntNotas = 0; @endphp
    @foreach($plan as $pe)
        @php
            $siglas = $pe->siglas_materia;
            $evMat  = $evaluaciones->get($siglas, collect());
            $n1     = optional($evMat->firstWhere('numero_momento', 1))->nota;
            $n2     = optional($evMat->firstWhere('numero_momento', 2))->nota;
            $n3     = optional($evMat->firstWhere('numero_momento', 3))->nota;
            $vals   = array_filter([$n1, $n2, $n3], fn($v) => $v !== null);
            $def    = count($vals) ? round(array_sum($vals) / count($vals), 2) : null;
            $resultado = $def !== null ? ($def >= $nota_minima ? 'A' : 'R') : '—';
            if ($def !== null) { $sumaNotas += $def; $cntNotas++; }
        @endphp
        <tr>
            <td class="materia-nombre">{{ $pe->materia->nombre ?? $siglas }}</td>
            @if(!$numero_momento || $numero_momento >= 1)
                <td class="{{ $n1 !== null && $n1 < $nota_minima ? 'nota-baja' : '' }}">{{ $n1 ?? '—' }}</td>
            @endif
            @if(!$numero_momento || $numero_momento >= 2)
                <td class="{{ $n2 !== null && $n2 < $nota_minima ? 'nota-baja' : '' }}">{{ $n2 ?? '—' }}</td>
            @endif
            @if(!$numero_momento || $numero_momento >= 3)
                <td class="{{ $n3 !== null && $n3 < $nota_minima ? 'nota-baja' : '' }}">{{ $n3 ?? '—' }}</td>
            @endif
            <td class="{{ $def !== null && $def < $nota_minima ? 'nota-baja' : '' }}">{{ $def ?? '—' }}</td>
            <td class="resultado-{{ $resultado }}">{{ $resultado === 'A' ? 'Aprobado' : ($resultado === 'R' ? 'Reprobado' : 'Sin nota') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<!-- RESUMEN -->
@php $promedioGral = $cntNotas ? round($sumaNotas / $cntNotas, 2) : null; @endphp
<div class="resumen">
    <div class="resumen-item">
        <div class="dato-etiqueta">Promedio General</div>
        <div class="resumen-valor {{ $promedioGral !== null && $promedioGral < $nota_minima ? 'resultado-R' : 'resultado-A' }}">
            {{ $promedioGral ?? '—' }}
        </div>
    </div>
    <div class="resumen-item">
        <div class="dato-etiqueta">Nota mínima aprobatoria</div>
        <div class="resumen-valor">{{ $nota_minima }}</div>
    </div>
    <div class="resumen-item">
        <div class="dato-etiqueta">Condición</div>
        <div class="resumen-valor {{ $promedioGral !== null && $promedioGral >= $nota_minima ? 'resultado-A' : 'resultado-R' }}">
            @if($promedioGral === null) Pendiente
            @elseif($promedioGral >= $nota_minima) Aprobado
            @else Reprobado
            @endif
        </div>
    </div>
    <div class="resumen-item">
        <div class="dato-etiqueta">Fecha de Emisión</div>
        <div class="resumen-valor" style="font-size:10pt">{{ $fecha_emision }}</div>
    </div>
</div>

<!-- FIRMAS -->
<div class="firmas">
    <div class="firma-bloque">
        <div class="firma-linea">&nbsp;</div>
        <p class="firma-nombre">{{ strtoupper($institucion->director?->nombre_completo ?? 'DIRECTOR(A)') }}</p>
        <p class="firma-cargo">Director(a) del Plantel</p>
    </div>
    <div class="firma-bloque">
        <div class="firma-linea">&nbsp;</div>
        <p class="firma-nombre">{{ strtoupper($institucion->coordinador?->nombre_completo ?? 'COORDINADOR(A)') }}</p>
        <p class="firma-cargo">Coordinador(a) Académico(a)</p>
    </div>
</div>

<div class="footer">
    Este documento es válido solo con el sello húmedo de la institución. Generado por SGAE — {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
