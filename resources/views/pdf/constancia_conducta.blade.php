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
    .header-text .rep  { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; }
    .header-text h1    { font-size: 9.5pt; text-transform: uppercase; font-weight: bold; }
    .header-text .cod  { font-size: 7.5pt; }
    .folio { text-align: right; font-size: 8pt; margin-bottom: 4px; }
    .doc-title { text-align: center; margin: 12px 0 18px; }
    .doc-title h2 { font-size: 15pt; font-weight: bold; text-transform: uppercase; letter-spacing: 3px;
                    border-bottom: 2px solid #000; display: inline-block; padding-bottom: 3px; }
    .cuerpo { text-align: justify; font-size: 11pt; line-height: 1.9; margin: 0 15px; }
    .cuerpo .enfasis { font-weight: bold; text-transform: uppercase; }
    .cuerpo .subr { text-decoration: underline; }
    .firmas { display: flex; justify-content: space-around; margin-top: 60px; }
    .firma-bloque { text-align: center; width: 42%; }
    .firma-linea { border-top: 1px solid #000; margin-bottom: 4px; }
    .firma-nombre { font-weight: bold; font-size: 9pt; text-transform: uppercase; }
    .firma-cargo { font-size: 8pt; font-style: italic; }
    .sello { text-align: center; margin-top: 20px; border: 1px dashed #aaa; padding: 12px;
             font-size: 8pt; color: #888; width: 120px; margin-left: auto; margin-right: auto; }
    .footer { text-align: center; font-size: 7.5pt; color: #666; margin-top: 16px;
              border-top: 1px solid #ccc; padding-top: 4px; }
</style>
</head>
<body>

<!-- ENCABEZADO ESTILO GESMAN/MPPE -->
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
            <div style="width:68px;"></div>
        @endif

        <div class="header-text">
            <p class="rep">República Bolivariana de Venezuela</p>
            <p class="rep">Ministerio del Poder Popular para la Educación</p>
            @if($institucion && $institucion->estado)
            <p class="rep">Estado Bolivariano de {{ $institucion->estado }}</p>
            @endif
            <h1>{{ $institucion->nombre ?? 'INSTITUCIÓN EDUCATIVA' }}</h1>
            <p class="cod">Código DEA: {{ $institucion->codigo_dea ?? '' }}
                @if($institucion && $institucion->zona_educativa)
                &nbsp;|&nbsp; Zona Educativa: {{ $institucion->zona_educativa }}
                @endif
            </p>
            @if($institucion && $institucion->telefono)
            <p class="cod">Teléfono: {{ $institucion->telefono }}</p>
            @endif
        </div>

        @if(file_exists($escudoPath))
            <img class="header-logo-r" src="{{ $escudoPath }}" alt="Escudo">
        @else
            <div style="width:68px;"></div>
        @endif
    </div>
</div>

<p class="folio">Nro: <strong>{{ $folio }}</strong></p>

<!-- TÍTULO -->
<div class="doc-title">
    <h2>Constancia de Buena Conducta</h2>
</div>

<!-- CUERPO -->
<div class="cuerpo">
    <p>
        Quien suscribe,
        <span class="enfasis">{{ strtoupper($institucion->director?->nombre_completo ?? '__________________________') }}</span>,
        en su carácter de Director(a) de la
        <span class="enfasis">{{ strtoupper($institucion->nombre ?? 'esta institución educativa') }}</span>,
        ubicada en {{ $institucion->municipio ?? '' }}, estado {{ $institucion->estado ?? '' }},
        Zona Educativa {{ $institucion->zona_educativa ?? '' }},
        adscrita al Ministerio del Poder Popular para la Educación,
        hace constar que:
    </p>
    <br>
    <p>
        El/La ciudadano(a)
        <span class="enfasis subr">{{ strtoupper($estudiante->apellidos) }}, {{ strtoupper($estudiante->nombres) }}</span>,
        titular de la Cédula de Identidad Nro.
        <span class="enfasis">{{ $estudiante->tipo_documento ?? 'V' }}-{{ $estudiante->cedula_estudiante }}</span>,
        @if($estudiante->fecha_nacimiento)
        nacido(a) el <span class="enfasis">{{ \Carbon\Carbon::parse($estudiante->fecha_nacimiento)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span>,
        @endif
        es alumno(a) regular de esta institución, cursando
        <span class="enfasis">{{ $seccion->grado->nombre ?? '' }}</span>
        año, Sección <span class="enfasis">{{ $seccion->letra }}</span>
        @if($seccion->mencion)
            , Mención <span class="enfasis">{{ strtoupper($seccion->mencion->nombre) }}</span>
        @endif
        , turno <span class="enfasis">{{ ucfirst($seccion->turno ?? '') }}</span>,
        durante el Año Escolar <span class="enfasis">{{ $anio->codigo_ano_escolar }}</span>.
    </p>
    <br>
    <p>
        Durante su permanencia en esta institución, el/la referido(a) estudiante ha demostrado una conducta
        <span class="enfasis">ejemplar y ajustada a las normas de convivencia escolar</span> establecidas por
        el Reglamento Interno y las disposiciones del Ministerio del Poder Popular para la Educación.
    </p>
    <br>
    <p>
        La presente constancia se expide a petición de la parte interesada,
        en {{ $institucion->municipio ?? 'la ciudad' }},
        a los <strong>{{ $fecha_emision }}</strong>.
    </p>
</div>

<!-- FIRMAS -->
<div class="firmas">
    <div class="firma-bloque">
        <br><br>
        <div class="firma-linea">&nbsp;</div>
        <p class="firma-nombre">{{ strtoupper($institucion->director?->nombre_completo ?? 'DIRECTOR(A)') }}</p>
        <p class="firma-cargo">Director(a) del Plantel</p>
    </div>
    <div class="sello">SELLO<br>HÚMEDO</div>
</div>

<div class="footer">
    Constancia Nro. {{ $folio }} — {{ $institucion->nombre ?? 'SGAE' }} — Generada el {{ now()->format('d/m/Y') }}
</div>

</body>
</html>
