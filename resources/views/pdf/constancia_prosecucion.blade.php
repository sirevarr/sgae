<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 11pt; color: #000; line-height: 1.6; }
    .header { width: 100%; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 14px; }
    .header-inner { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .header-logo, .header-logo-r { width: 68px; height: 68px; object-fit: contain; flex-shrink: 0; }
    .header-text { text-align: center; flex: 1; line-height: 1.45; }
    .header-text .rep { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; }
    .header-text h1   { font-size: 9.5pt; text-transform: uppercase; font-weight: bold; }
    .header-text .cod { font-size: 7.5pt; }
    .doc-title { text-align: center; margin: 12px 0 4px; }
    .doc-title h2 { font-size: 14pt; font-weight: bold; text-transform: uppercase; letter-spacing: 2px;
                    border-bottom: 2px solid #000; display: inline-block; padding-bottom: 2px; }
    .doc-subtitulo { text-align: center; font-size: 10pt; font-weight: bold; margin-bottom: 16px; text-transform: uppercase; }
    .cuerpo { text-align: justify; font-size: 11pt; line-height: 1.9; margin: 0 15px; }
    .cuerpo .enfasis { font-weight: bold; text-transform: uppercase; }
    .cuerpo .subr { text-decoration: underline; }

    /* Tabla de doble firma estilo Gesman prosecución */
    .tabla-firmas {
        width: 100%;
        border-collapse: collapse;
        margin-top: 32px;
        font-size: 9.5pt;
    }
    .tabla-firmas th {
        background: #1a1a2e;
        color: #fff;
        text-align: center;
        padding: 8px 6px;
        border: 1px solid #000;
        font-size: 9pt;
    }
    .tabla-firmas td {
        border: 1px solid #555;
        padding: 10px 8px;
        vertical-align: top;
        font-size: 9pt;
    }
    .tabla-firmas .espacio-firma { height: 40px; }
    .footer { text-align: center; font-size: 7.5pt; color: #666; margin-top: 16px;
              border-top: 1px solid #ccc; padding-top: 4px; }
</style>
</head>
<body>

<!-- ENCABEZADO ESTILO GESMAN/MPPE -->
<div class="header">
    <div class="header-inner">
        @php
            $logoPath   = public_path('imagenes/logo_izq.png');
            $escudoPath = public_path('imagenes/logo_der.png');
            $logoSGAE   = public_path('imagenes/SGAE.png');
            // Grado promovido (siguiente año)
            $gradoActualNum  = $seccion->grado->numero_ano ?? 0;
            $gradoPromovidoNum = $gradoActualNum + 1;
            $ordinal = match($gradoPromovidoNum) {
                1 => '1er', 2 => '2do', 3 => '3er', 4 => '4to', 5 => '5to', default => $gradoPromovidoNum . '°'
            };
        @endphp
        @if(file_exists($logoPath))
            <img class="header-logo" src="{{ $logoPath }}" alt="Logo">
        @elseif(file_exists($logoSGAE))
            <img class="header-logo" src="{{ $logoSGAE }}" alt="Logo">
        @else
            <div style="width:68px;"></div>
        @endif

        <div class="header-text">
            <p class="rep">República Bolivariana de Venezuela</p>
            <p class="rep">Ministerio del Poder Popular para la Educación</p>
            @if($institucion?->estado)<p class="rep">Estado Bolivariano de {{ $institucion->estado }}</p>@endif
            <h1>{{ $institucion->nombre ?? 'INSTITUCIÓN EDUCATIVA' }}</h1>
            <p class="cod">Código DEA: {{ $institucion->codigo_dea ?? '' }}
                @if($institucion?->zona_educativa) &nbsp;|&nbsp; Zona Educativa: {{ $institucion->zona_educativa }} @endif
            </p>
            @if($institucion?->telefono)<p class="cod">Teléfono: {{ $institucion->telefono }}</p>@endif
        </div>

        @if(file_exists($escudoPath))
            <img class="header-logo-r" src="{{ $escudoPath }}" alt="Escudo">
        @else
            <div style="width:68px;"></div>
        @endif
    </div>
</div>

<!-- TÍTULO -->
<div class="doc-title">
    <h2>Constancia de Prosecución</h2>
</div>
<p class="doc-subtitulo">En el Nivel de Educación Media General</p>

<!-- CUERPO -->
<div class="cuerpo">
    <p>
        Quien suscribe,
        <span class="enfasis">{{ strtoupper($institucion->director?->nombre_completo ?? '__________________________') }}</span>,
        titular de la Cédula de Identidad Nº
        <span class="enfasis">{{ $institucion->director?->cedula_personal ?? '___________' }}</span>,
        en su carácter de Director(a) de la Institución Educativa
        <span class="enfasis">{{ strtoupper($institucion->nombre ?? 'esta institución educativa') }}</span>,
        ubicada en el Municipio <span class="enfasis">{{ strtoupper($institucion->municipio ?? '') }}</span>,
        estado <span class="enfasis">{{ strtoupper($institucion->estado ?? '') }}</span>,
        Zona Educativa <span class="enfasis">{{ $institucion->zona_educativa ?? '' }}</span>,
        adscrita al Ministerio del Poder Popular para la Educación;
        por la presente hace constar que el (la) estudiante:
    </p>
    <br>
    <p>
        <span class="enfasis subr">{{ strtoupper($estudiante->apellidos) }}, {{ strtoupper($estudiante->nombres) }}</span>,
        titular de la Cédula Escolar Nro.
        <span class="enfasis">{{ $estudiante->tipo_documento ?? 'V' }}-{{ $estudiante->cedula_estudiante }}</span>,
        @if($estudiante->lugar_nacimiento || $estudiante->estado_nacimiento)
        nacido(a) en
        @if($estudiante->lugar_nacimiento)<span class="enfasis">{{ strtoupper($estudiante->lugar_nacimiento) }}</span>@endif
        @if($estudiante->estado_nacimiento), Estado <span class="enfasis">{{ strtoupper($estudiante->estado_nacimiento) }}</span>@endif,
        @endif
        @if($estudiante->fecha_nacimiento)
        en fecha <span class="enfasis">{{ \Carbon\Carbon::parse($estudiante->fecha_nacimiento)->format('d/m/Y') }}</span>,
        @endif
        cursó el <span class="enfasis">{{ $seccion->grado->nombre ?? '' }}</span>,
        Sección <span class="enfasis">{{ $seccion->letra }}</span>
        @if($seccion->mencion), Mención <span class="enfasis">{{ strtoupper($seccion->mencion->nombre) }}</span>@endif,
        durante el período escolar <span class="enfasis">{{ $anio->codigo_ano_escolar }}</span>,
        siendo promovido(a) al <span class="enfasis">{{ $ordinal }} Año</span> del Nivel de Educación Media General,
        previo cumplimiento de los requisitos establecidos en la normativa legal vigente.
    </p>
    <br>
    <p>
        Constancia que se expide en <span class="enfasis">{{ strtoupper($institucion->municipio ?? 'la ciudad') }}</span>,
        a los <strong>{{ $fecha_emision }}</strong>.
    </p>
</div>

<!-- TABLA DE DOBLE FIRMA (estilo Gesman prosecución) -->
<table class="tabla-firmas">
    <thead>
        <tr>
            <th>INSTITUCIÓN EDUCATIVA<br><small>(Para Validez Nacional)</small></th>
            <th>CENTRO DE DESARROLLO DE LA CALIDAD EDUCATIVA ESTADAL<br><small>(Para Validez Internacional)</small></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>DIRECTOR(A)</td>
            <td>DIRECTOR(A)</td>
        </tr>
        <tr>
            <td>Nombre y Apellido: <strong>{{ $institucion->director?->nombre_completo ?? '___________________________' }}</strong></td>
            <td>Nombre y Apellido: ___________________________</td>
        </tr>
        <tr>
            <td>Número de C.I.: {{ $institucion->director?->cedula_personal ?? '___________' }}</td>
            <td>Número de C.I.: ___________</td>
        </tr>
        <tr>
            <td class="espacio-firma">Firma y Sello:</td>
            <td class="espacio-firma">Firma y Sello:</td>
        </tr>
    </tbody>
</table>

<div class="footer">
    {{ $institucion->nombre ?? 'SGAE' }} — Generada el {{ now()->format('d/m/Y') }}
</div>
</body>
</html>
