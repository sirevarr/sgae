<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; line-height: 1.6; }
    .header { width: 100%; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 14px; }
    .republic-line { font-size: 9pt; font-weight: bold; text-align: center; margin-bottom: 4px; }
    .header-inner { display: flex; align-items: center; justify-content: center; gap: 12px; }
    .header-logo { width: 65px; height: 65px; }
    .header-text { text-align: center; }
    .header-text h1 { font-size: 11pt; text-transform: uppercase; font-weight: bold; }
    .header-text h2 { font-size: 10pt; text-transform: uppercase; font-weight: bold; }
    .header-text p  { font-size: 8.5pt; }
    .folio { text-align: right; font-size: 8pt; margin-bottom: 6px; }
    .doc-title { text-align: center; margin: 14px 0 20px; }
    .doc-title h2 { font-size: 15pt; font-weight: bold; text-transform: uppercase; letter-spacing: 3px; border-bottom: 2px solid #000; display: inline-block; padding-bottom: 3px; }
    .cuerpo { text-align: justify; font-size: 11pt; line-height: 1.8; margin: 0 20px; }
    .cuerpo .enfasis { font-weight: bold; text-transform: uppercase; }
    .cuerpo .subrayado { text-decoration: underline; }
    .firmas { display: flex; justify-content: space-around; margin-top: 60px; }
    .firma-bloque { text-align: center; width: 40%; }
    .firma-linea { border-top: 1px solid #000; margin-bottom: 4px; }
    .firma-nombre { font-weight: bold; font-size: 9.5pt; text-transform: uppercase; }
    .firma-cargo  { font-size: 8.5pt; font-style: italic; }
    .sello { text-align: center; margin-top: 20px; border: 1px dashed #aaa; padding: 12px; font-size: 8pt; color: #888; width: 120px; margin-left: auto; margin-right: auto; }
    .footer { text-align: center; font-size: 7.5pt; color: #666; margin-top: 20px; border-top: 1px solid #ccc; padding-top: 5px; }
</style>
</head>
<body>

<div class="header">
    <p class="republic-line">REPÚBLICA BOLIVARIANA DE VENEZUELA — MINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN</p>
    <div class="header-inner">
        @if(file_exists(public_path('imagenes/SGAE.png')))
        <img class="header-logo" src="{{ public_path('imagenes/SGAE.png') }}" alt="Logo">
        @endif
        <div class="header-text">
            <h1>{{ $institucion->nombre ?? 'INSTITUCIÓN EDUCATIVA' }}</h1>
            <h2>{{ $institucion->municipio ?? '' }} — {{ $institucion->estado ?? '' }}</h2>
            <p>Zona Educativa: {{ $institucion->zona_educativa ?? '' }}</p>
            <p>Código DEA: {{ $institucion->codigo_dea ?? '' }}</p>
        </div>
    </div>
</div>

<p class="folio">Nro: <strong>{{ $folio }}</strong></p>

<div class="doc-title">
    <h2>Constancia de Buena Conducta</h2>
</div>

<div class="cuerpo">
    <p>
        Quien suscribe,
        <span class="enfasis">{{ strtoupper($institucion->director?->nombre_completo ?? '__________________________') }}</span>,
        en su carácter de Director(a) de la
        <span class="enfasis">{{ strtoupper($institucion->nombre ?? 'esta institución educativa') }}</span>,
        Zona Educativa {{ $institucion->zona_educativa ?? '' }}, estado {{ $institucion->estado ?? '' }},
        adscrita al Ministerio del Poder Popular para la Educación,
        hace constar que:
    </p>
    <br>
    <p>
        El/La ciudadano(a)
        <span class="enfasis subrayado">{{ strtoupper($estudiante->apellidos) }}, {{ strtoupper($estudiante->nombres) }}</span>,
        titular de la Cédula de Identidad Nro.
        <span class="enfasis">{{ $estudiante->tipo_documento ?? 'V' }}-{{ $estudiante->cedula_estudiante }}</span>,
        alumno(a) de
        <span class="enfasis">{{ $seccion->grado->nombre ?? '' }}</span>
        año, Sección <span class="enfasis">{{ $seccion->letra }}</span>,
        durante el Año Escolar <span class="enfasis">{{ $anio->codigo_ano_escolar }}</span>,
        ha mantenido una <span class="enfasis">conducta intachable</span>,
        demostrando en todo momento respeto, responsabilidad y compromiso con los valores de la institución y de la comunidad educativa.
    </p>
    <br>
    <p>
        La presente constancia se expide a petición de la parte interesada,
        en {{ $institucion->municipio ?? 'la ciudad' }}, a los <strong>{{ $fecha_emision }}</strong>,
        para los fines que el solicitante estime convenientes.
    </p>
</div>

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
