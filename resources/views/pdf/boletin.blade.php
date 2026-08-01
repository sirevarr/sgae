<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; color: #000; }

    /* ── ENCABEZADO INSTITUCIONAL (estilo Gesman/MPPE) ── */
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
    .header-logo {
        width: 68px;
        height: 68px;
        object-fit: contain;
        flex-shrink: 0;
    }
    .header-text {
        text-align: center;
        flex: 1;
        line-height: 1.4;
    }
    .header-text .rep { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; }
    .header-text .estado { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; }
    .header-text h1 { font-size: 9.5pt; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px; }
    .header-text .cod { font-size: 7.5pt; }
    .header-text .dir { font-size: 7.5pt; }
    .header-text .tel { font-size: 7.5pt; }
    .header-logo-r {
        width: 68px;
        height: 68px;
        object-fit: contain;
        flex-shrink: 0;
    }

    /* ── TÍTULO DEL DOCUMENTO ── */
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
    .tipo-boletin {
        text-align: center;
        font-size: 9pt;
        font-weight: bold;
        margin-bottom: 6px;
    }

    /* ── DATOS DEL ESTUDIANTE ── */
    .datos-estudiante {
        display: flex;
        gap: 0;
        margin-bottom: 8px;
        border: 1.5px solid #222;
        padding: 0;
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

    /* ── TABLA DE NOTAS ── */
    table.notas {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
        font-size: 9pt;
    }
    table.notas thead tr {
        background: #1a1a2e;
        color: #fff;
    }
    table.notas th {
        padding: 5px 3px;
        text-align: center;
        border: 1px solid #000;
        font-size: 8pt;
        font-weight: bold;
    }
    table.notas td {
        padding: 4px 3px;
        border: 1px solid #333;
        text-align: center;
    }
    table.notas tr:nth-child(even) td { background: #f4f4f4; }
    table.notas .materia-nombre { text-align: left; padding-left: 6px; font-size: 8.5pt; }
    table.notas .nota-baja { color: #b71c1c; font-weight: bold; }
    table.notas .nota-alta { color: #1b5e20; font-weight: bold; }
    .res-A { color: #1b5e20; font-weight: bold; }
    .res-R { color: #b71c1c; font-weight: bold; }
    /* RF-05: estado En Revisión */
    .res-V { color: #c65a00; font-weight: bold; }
    /* RF-04: literales A-E */
    .lit-A { color: #1b5e20; font-weight: bold; }
    .lit-B { color: #2e7d32; font-weight: bold; }
    .lit-C { color: #f57f17; font-weight: bold; }
    .lit-D { color: #e65100; font-weight: bold; }
    .lit-E { color: #b71c1c; font-weight: bold; }
    .revision-mark { font-size: 6.5pt; color: #c65a00; font-style: italic; }

    /* ── MATERIAS PENDIENTES ── */
    .pendientes-section {
        margin-top: 8px;
        border: 1px solid #f57f17;
        padding: 5px 8px;
        background: #fffde7;
    }
    .pendientes-title {
        font-weight: bold;
        font-size: 8pt;
        color: #e65100;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    table.pendientes {
        width: 100%;
        border-collapse: collapse;
        font-size: 8pt;
    }
    table.pendientes th {
        background: #e65100;
        color: #fff;
        padding: 3px 4px;
        border: 1px solid #bf360c;
        font-size: 7.5pt;
    }
    table.pendientes td {
        padding: 3px 4px;
        border: 1px solid #e0a000;
        text-align: center;
    }
    .pend-estado-pendiente { color: #c62828; font-weight: bold; }
    .pend-estado-aprobada  { color: #1b5e20; font-weight: bold; }

    /* ── FICHA ANTROPOMÉTRICA ── */
    .ficha-section {
        margin-top: 6px;
        display: flex;
        gap: 6px;
        border: 1px solid #1565c0;
        padding: 5px 8px;
        background: #e3f2fd;
        font-size: 8pt;
    }
    .ficha-item { flex: 1; text-align: center; }
    .ficha-label { font-weight: bold; color: #0d47a1; font-size: 7pt; text-transform: uppercase; }
    .ficha-valor { font-size: 10pt; font-weight: bold; }

    /* ── RESUMEN FINAL ── */
    .resumen {
        border: 1.5px solid #000;
        padding: 6px 10px;
        margin-top: 8px;
        display: flex;
        justify-content: space-between;
        font-size: 9pt;
        background: #fafafa;
    }
    .resumen-item { text-align: center; }
    .resumen-label { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; color: #444; }
    .resumen-valor { font-size: 14pt; font-weight: bold; }

    /* ── OBSERVACIONES ── */
    .observaciones-section {
        margin-top: 6px;
        border: 1px solid #555;
        padding: 5px 8px;
    }
    .obs-label { font-weight: bold; font-size: 8pt; color: #333; }
    .obs-texto { font-size: 9pt; margin-top: 2px; min-height: 30px; }

    /* ── FIRMAS ── */
    .firmas {
        display: flex;
        justify-content: space-around;
        margin-top: 36px;
    }
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

    .page-break { page-break-after: always; }
</style>
</head>
<body>

<!-- ENCABEZADO INSTITUCIONAL ESTILO GESMAN/MPPE -->
<div class="header">
    <div class="header-inner">
        @php
            $logoPath = public_path('imagenes/logo_izq.png');
            $escudoPath = public_path('imagenes/logo_der.png');
            $logoSGAE = public_path('imagenes/SGAE.png');
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
            <p class="dir">{{ $institucion->municipio }}{{ $institucion->estado ? ' — ' . $institucion->estado : '' }}</p>
            @endif
            @if($institucion && $institucion->telefono)
            <p class="tel">Teléfono: {{ $institucion->telefono }}</p>
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
    <h2>Boletín de Calificaciones</h2>
</div>
<p class="tipo-boletin">
    Año Escolar: <strong>{{ $anio->codigo_ano_escolar }}</strong>
    &nbsp;|&nbsp;
    @if($numero_momento)
        Momento Evaluativo: <strong>{{ $numero_momento }}°</strong>
    @else
        <strong>Evaluación Final (3 Momentos)</strong>
    @endif
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
        <p class="dato-etiqueta">Grado y Sección</p>
        <p class="dato-valor">{{ $seccion->grado->nombre ?? '' }} &nbsp; Secc. {{ $seccion->letra }}</p>
        <p class="dato-etiqueta">Mención</p>
        <p class="dato-valor">{{ $seccion->mencion->nombre ?? '—' }}</p>
    </div>
    <div class="dato-bloque">
        <p class="dato-etiqueta">N° de Lista</p>
        <p class="dato-valor">{{ $matricula->numero_lista ?? '—' }}</p>
        <p class="dato-etiqueta">Turno</p>
        <p class="dato-valor">{{ ucfirst($seccion->turno ?? '—') }}</p>
    </div>
    <div class="dato-bloque">
        <p class="dato-etiqueta">Fecha de Nacimiento</p>
        <p class="dato-valor">{{ $estudiante->fecha_nacimiento ? \Carbon\Carbon::parse($estudiante->fecha_nacimiento)->format('d/m/Y') : '—' }}</p>
        <p class="dato-etiqueta">Cond. Ingreso</p>
        <p class="dato-valor">{{ ucfirst($matricula->condicion_ingreso ?? '—') }}</p>
    </div>
</div>

<!-- TABLA DE NOTAS -->
<table class="notas">
    <thead>
        <tr>
            <th style="width:34%;text-align:left;padding-left:6px">Área de Formación / Materia</th>
            @if(!$numero_momento || $numero_momento >= 1)<th>1er Momento</th>@endif
            @if(!$numero_momento || $numero_momento >= 2)<th>2do Momento</th>@endif
            @if(!$numero_momento || $numero_momento >= 3)<th>3er Momento</th>@endif
            <th>Definitiva</th>
            <th style="width:42px;">Literal</th>
            <th>Resultado</th>
        </tr>
    </thead>
    <tbody>
    @php $sumaNotas = 0; $cntNotas = 0; $materAprobadas = 0; $materReprobadas = 0; @endphp
    @foreach($plan as $pe)
        @php
            $siglas = $pe->siglas_materia;
            $evMat  = $evaluaciones->get($siglas, collect());
            $n1     = optional($evMat->firstWhere('numero_momento', 1))->nota;
            $n2     = optional($evMat->firstWhere('numero_momento', 2))->nota;
            $n3     = optional($evMat->firstWhere('numero_momento', 3))->nota;
            $esRevision = $evMat->where('es_revision', true)->count() > 0;
            $vals   = array_filter([$n1, $n2, $n3], fn($v) => $v !== null);
            $def    = count($vals) ? round(array_sum($vals) / count($vals), 2) : null;
            // Tipo de evaluación: L = literal (1=A,0=R), N = numérica
            $tipoEval = $pe->tipo_evaluacion ?? 'N';
            if ($tipoEval === 'L') {
                if ($def === null) {
                    $resultado = '—';
                    $literal   = '—';
                } else {
                    $resultado = ($def >= $nota_minima || $def == 1) ? 'A' : ($esRevision ? 'V' : 'R');
                    if ($def >= 18)      $literal = 'A';
                    elseif ($def >= 15)  $literal = 'B';
                    elseif ($def >= 12)  $literal = 'C';
                    elseif ($def >= 10)  $literal = 'D';
                    elseif ($def == 1)   $literal = 'A';
                    else                 $literal = 'E';
                }
            } else {
                if ($def === null) {
                    $resultado = '—';
                    $literal   = null;
                } elseif ($def >= $nota_minima) {
                    $resultado = 'A';
                } elseif ($esRevision) {
                    $resultado = 'V'; // RF-05: En Revisión
                } else {
                    $resultado = 'R';
                }
                // RF-04: calcular literal A-E
                if ($def !== null) {
                    if ($def >= 18)      $literal = 'A';
                    elseif ($def >= 15)  $literal = 'B';
                    elseif ($def >= 12)  $literal = 'C';
                    elseif ($def >= 10)  $literal = 'D';
                    else                 $literal = 'E';
                } else {
                    $literal = null;
                }
            }
            if ($def !== null) {
                $sumaNotas += $def;
                $cntNotas++;
                if ($resultado === 'A') $materAprobadas++;
                elseif ($resultado === 'R' || $resultado === 'V') $materReprobadas++;
            }
        @endphp
        <tr>
            <td class="materia-nombre">
                {{ $pe->materia->nombre ?? $siglas }}
                @if($esRevision)<span class="revision-mark"> (R)</span>@endif
            </td>
            @if(!$numero_momento || $numero_momento >= 1)
                <td class="{{ $n1 !== null && $tipoEval !== 'L' && $n1 < $nota_minima ? 'nota-baja' : ($n1 >= $nota_minima ? 'nota-alta' : '') }}">
                    @if($tipoEval === 'L')
                        {{ $n1 !== null ? ($n1 == 1 ? 'A' : 'R') : '—' }}
                    @else
                        {{ $n1 ?? '—' }}
                    @endif
                </td>
            @endif
            @if(!$numero_momento || $numero_momento >= 2)
                <td class="{{ $n2 !== null && $tipoEval !== 'L' && $n2 < $nota_minima ? 'nota-baja' : ($n2 >= $nota_minima ? 'nota-alta' : '') }}">
                    @if($tipoEval === 'L')
                        {{ $n2 !== null ? ($n2 == 1 ? 'A' : 'R') : '—' }}
                    @else
                        {{ $n2 ?? '—' }}
                    @endif
                </td>
            @endif
            @if(!$numero_momento || $numero_momento >= 3)
                <td class="{{ $n3 !== null && $tipoEval !== 'L' && $n3 < $nota_minima ? 'nota-baja' : ($n3 >= $nota_minima ? 'nota-alta' : '') }}">
                    @if($tipoEval === 'L')
                        {{ $n3 !== null ? ($n3 == 1 ? 'A' : 'R') : '—' }}
                    @else
                        {{ $n3 ?? '—' }}
                    @endif
                </td>
            @endif
            <td class="{{ $def !== null && $tipoEval !== 'L' && $def < $nota_minima ? 'nota-baja' : '' }}">
                @if($tipoEval === 'L')
                    {{ $def !== null ? ($def == 1 ? 'A' : 'R') : '—' }}
                @else
                    {{ $def ?? '—' }}
                @endif
            </td>
            {{-- RF-04: Columna Literal (solo para evaluaciones numéricas) --}}
            <td class="{{ $literal ? 'lit-' . $literal : '' }}">
                {{ $literal ?? '—' }}
            </td>
            {{-- RF-05: Resultado con estado En Revisión --}}
            <td class="res-{{ $resultado }}">
                @if($resultado === 'A') Aprobado
                @elseif($resultado === 'R') Reprobado
                @elseif($resultado === 'V') En Revisión
                @else Sin nota
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<!-- FICHA ANTROPOMÉTRICA (si existe) -->
@if(isset($ficha) && $ficha)
<div class="ficha-section">
    <div class="ficha-item">
        <p class="ficha-label">Estatura</p>
        <p class="ficha-valor">{{ $ficha->estatura ? number_format($ficha->estatura, 2) . ' m' : '—' }}</p>
    </div>
    <div class="ficha-item">
        <p class="ficha-label">Peso</p>
        <p class="ficha-valor">{{ $ficha->peso ? number_format($ficha->peso, 1) . ' kg' : '—' }}</p>
    </div>
    @if($ficha->talla_camisa)
    <div class="ficha-item">
        <p class="ficha-label">Talla Camisa</p>
        <p class="ficha-valor">{{ $ficha->talla_camisa }}</p>
    </div>
    @endif
    @if($ficha->talla_pantalon)
    <div class="ficha-item">
        <p class="ficha-label">Talla Pantalón</p>
        <p class="ficha-valor">{{ $ficha->talla_pantalon }}</p>
    </div>
    @endif
    @if($ficha->talla_zapatos)
    <div class="ficha-item">
        <p class="ficha-label">Talla Zapatos</p>
        <p class="ficha-valor">{{ $ficha->talla_zapatos }}</p>
    </div>
    @endif
    <div class="ficha-item">
        <p class="ficha-label">Fecha Medición</p>
        <p class="ficha-valor" style="font-size:9pt">{{ $ficha->fecha_medicion ? \Carbon\Carbon::parse($ficha->fecha_medicion)->format('d/m/Y') : '—' }}</p>
    </div>
</div>
@endif

<!-- MATERIAS PENDIENTES (si tiene) -->
@if(isset($pendientes) && $pendientes->count() > 0)
<div class="pendientes-section">
    <p class="pendientes-title">⚠ Materias Pendientes / Aplazadas</p>
    <table class="pendientes">
        <thead>
            <tr>
                <th>Materia</th>
                <th>Año Escolar Origen</th>
                <th>Estado</th>
                <th>Nota Final</th>
                <th>Fecha Resolución</th>
            </tr>
        </thead>
        <tbody>
        @foreach($pendientes as $pend)
            <tr>
                <td style="text-align:left;padding-left:5px">{{ $pend->materia->nombre ?? $pend->siglas_materia }}</td>
                <td>{{ $pend->codigo_ano_escolar_origen }}</td>
                <td class="pend-estado-{{ $pend->estado }}">{{ ucfirst($pend->estado ?? '—') }}</td>
                <td>{{ $pend->nota_final ?? '—' }}</td>
                <td>{{ $pend->fecha_resolucion ? \Carbon\Carbon::parse($pend->fecha_resolucion)->format('d/m/Y') : '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- RESUMEN -->
@php $promedioGral = $cntNotas ? round($sumaNotas / $cntNotas, 2) : null; @endphp
<div class="resumen">
    <div class="resumen-item">
        <p class="resumen-label">Promedio General</p>
        <p class="resumen-valor {{ $promedioGral !== null && $promedioGral < $nota_minima ? 'res-R' : 'res-A' }}">
            {{ $promedioGral ?? '—' }}
        </p>
    </div>
    <div class="resumen-item">
        <p class="resumen-label">Nota Mínima</p>
        <p class="resumen-valor">{{ $nota_minima }}</p>
    </div>
    <div class="resumen-item">
        <p class="resumen-label">Aprobadas</p>
        <p class="resumen-valor res-A">{{ $materAprobadas }}</p>
    </div>
    <div class="resumen-item">
        <p class="resumen-label">Reprobadas</p>
        <p class="resumen-valor {{ $materReprobadas > 0 ? 'res-R' : '' }}">{{ $materReprobadas }}</p>
    </div>
    <div class="resumen-item">
        <p class="resumen-label">Condición</p>
        <p class="resumen-valor {{ $promedioGral !== null && $promedioGral >= $nota_minima ? 'res-A' : 'res-R' }}">
            @if($promedioGral === null) Pendiente
            @elseif($promedioGral >= $nota_minima) Aprobado
            @else Reprobado
            @endif
        </p>
    </div>
    <div class="resumen-item">
        <p class="resumen-label">Fecha de Emisión</p>
        <p class="resumen-valor" style="font-size:9pt">{{ $fecha_emision }}</p>
    </div>
</div>

<!-- OBSERVACIONES -->
@if(isset($observaciones) && $observaciones)
<div class="observaciones-section">
    <p class="obs-label">Observaciones:</p>
    <p class="obs-texto">{{ $observaciones }}</p>
</div>
@elseif($matricula->observaciones)
<div class="observaciones-section">
    <p class="obs-label">Observaciones:</p>
    <p class="obs-texto">{{ $matricula->observaciones }}</p>
</div>
@endif

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
    Este documento es válido solo con el sello húmedo de la institución.
    Generado por SGAE — {{ now()->format('d/m/Y H:i') }}
    @if(isset($esRevision) && $esRevision)
        &nbsp;|&nbsp; <strong>(R) = Nota de Revisión</strong>
    @endif
</div>

</body>
</html>
