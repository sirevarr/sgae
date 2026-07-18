<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 9pt; color: #000; }
    .header { border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 10px; }
    .republic-line { font-size: 8pt; font-weight: bold; text-align: center; }
    .header-inner { display: flex; align-items: center; justify-content: center; gap: 10px; }
    .header-logo { width: 55px; height: 55px; }
    .header-text { text-align: center; }
    .header-text h1 { font-size: 10pt; text-transform: uppercase; font-weight: bold; }
    .header-text p { font-size: 7.5pt; }
    .doc-title { text-align: center; font-size: 11pt; font-weight: bold; text-transform: uppercase; margin: 8px 0 6px; }
    .seccion-info { display: flex; gap: 20px; margin-bottom: 8px; border: 1px solid #000; padding: 5px; font-size: 8pt; }
    .si-item { flex: 1; }
    .si-label { font-weight: bold; font-size: 7pt; text-transform: uppercase; }
    table.lista { width: 100%; border-collapse: collapse; font-size: 8pt; }
    table.lista th { background: #1a1a2e; color: #fff; padding: 4px 3px; text-align: center; border: 1px solid #000; font-size: 7.5pt; }
    table.lista td { padding: 3px 3px; border: 1px solid #000; text-align: center; }
    table.lista .nombre-col { text-align: left; padding-left: 4px; }
    table.lista tr:nth-child(even) { background: #f0f0f0; }
    .firma-area { display: flex; justify-content: space-around; margin-top: 30px; }
    .firma-bloque { text-align: center; width: 35%; }
    .firma-linea { border-top: 1px solid #000; margin-bottom: 3px; }
    .firma-nombre { font-weight: bold; font-size: 7.5pt; text-transform: uppercase; }
    .footer { text-align: center; font-size: 7pt; color: #555; margin-top: 10px; border-top: 1px solid #ccc; padding-top: 4px; }
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
            <p>{{ $institucion->municipio ?? '' }} — {{ $institucion->estado ?? '' }}</p>
            <p>Año Escolar: <strong>{{ $anio->codigo_ano_escolar }}</strong></p>
        </div>
    </div>
</div>

<p class="doc-title">Lista de Sección</p>

<div class="seccion-info">
    <div class="si-item">
        <p class="si-label">Grado</p>
        <p>{{ $seccion->grado->nombre ?? '' }}</p>
    </div>
    <div class="si-item">
        <p class="si-label">Sección</p>
        <p>{{ $seccion->letra }}</p>
    </div>
    <div class="si-item">
        <p class="si-label">Turno</p>
        <p>{{ ucfirst($seccion->turno ?? '') }}</p>
    </div>
    <div class="si-item">
        <p class="si-label">Aula</p>
        <p>{{ $seccion->aula_asignada ?? '—' }}</p>
    </div>
    <div class="si-item">
        <p class="si-label">Docente Guía</p>
        <p>{{ $seccion->docenteGuia?->personal?->nombre_completo ?? '—' }}</p>
    </div>
    <div class="si-item">
        <p class="si-label">Total Estudiantes</p>
        <p><strong>{{ $matriculas->count() }}</strong></p>
    </div>
</div>

<table class="lista">
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:12%">Cédula</th>
            <th style="width:35%">Apellidos y Nombres</th>
            <th style="width:6%">Género</th>
            <th style="width:10%">F. Nacimiento</th>
            <th style="width:18%">Teléfono / Correo</th>
            <th style="width:15%">Observaciones</th>
        </tr>
    </thead>
    <tbody>
    @foreach($matriculas as $m)
        <tr>
            <td>{{ $m->numero_lista ?? $loop->iteration }}</td>
            <td>{{ $m->estudiante->tipo_documento ?? 'V' }}-{{ $m->estudiante->cedula_estudiante }}</td>
            <td class="nombre-col">{{ strtoupper($m->estudiante->apellidos) }}, {{ $m->estudiante->nombres }}</td>
            <td>{{ $m->estudiante->genero === 'M' ? 'Masc.' : 'Fem.' }}</td>
            <td>{{ $m->estudiante->fecha_nacimiento ? \Carbon\Carbon::parse($m->estudiante->fecha_nacimiento)->format('d/m/Y') : '—' }}</td>
            <td>{{ $m->estudiante->telefono ?? '—' }}</td>
            <td>{{ $m->observaciones ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="firma-area">
    <div class="firma-bloque">
        <br>
        <div class="firma-linea">&nbsp;</div>
        <p class="firma-nombre">{{ strtoupper($institucion->director?->nombre_completo ?? 'DIRECTOR(A)') }}</p>
        <p style="font-size:7pt; font-style:italic;">Director(a) del Plantel</p>
    </div>
    <div class="firma-bloque">
        <br>
        <div class="firma-linea">&nbsp;</div>
        <p class="firma-nombre">{{ strtoupper($seccion->docenteGuia?->personal?->nombre_completo ?? 'DOCENTE GUÍA') }}</p>
        <p style="font-size:7pt; font-style:italic;">Docente Guía</p>
    </div>
</div>

<div class="footer">
    Lista impresa el {{ $fecha_hoy }} — SGAE Sistema de Gestión Académica Escolar
</div>
</body>
</html>
