<?php
// Verificar EXACTAMENTE qué ve la lógica del boletín

$cedula = '30444444';
$anio = '2025-2026';

$matricula = \App\Models\Matricula::where('cedula_estudiante', $cedula)
    ->where('codigo_ano_escolar', $anio)
    ->where('estado_matricula', 'activa')
    ->with(['seccion.grado', 'seccion.mencion'])
    ->first();

if (!$matricula) {
    echo "ERROR: No hay matrícula activa\n";
    exit;
}

$seccion = $matricula->seccion;
echo "Sección: " . $seccion->codigo_seccion . "\n";
echo "Grado: " . $seccion->codigo_grado . "\n";
echo "Mención (id): " . $seccion->id_mencion . "\n";
echo "Mención (nombre): " . ($seccion->mencion->nombre ?? 'NULL') . "\n\n";

// Plan de estudios
$plan = \App\Models\PlanEstudios::with('materia')
    ->where('codigo_grado', $seccion->codigo_grado)
    ->where('id_mencion', $seccion->id_mencion)
    ->where('codigo_ano_escolar', $anio)
    ->get();

echo "Plan de estudios encontrado: " . $plan->count() . " materias\n";
foreach ($plan as $p) {
    echo "  " . $p->siglas_materia . " (" . ($p->materia->nombre ?? '?') . ")\n";
}

// Evaluaciones
$evaluaciones = \App\Models\Evaluacion::where('cedula_estudiante', $cedula)
    ->where('codigo_ano_escolar', $anio)
    ->get();

echo "\nEvaluaciones encontradas: " . $evaluaciones->count() . "\n";
foreach ($evaluaciones as $ev) {
    echo "  " . $ev->siglas_materia . " M" . $ev->numero_momento . " = " . $ev->nota;
    echo " | grado=" . $ev->codigo_grado . " mencion=" . $ev->id_mencion . "\n";
}

// Verificar match: ¿las evaluaciones se cruzan con el plan?
echo "\n--- CRUCE plan vs evaluaciones ---\n";
$evsGrouped = $evaluaciones->groupBy('siglas_materia');
foreach ($plan as $pe) {
    $evs = $evsGrouped->get($pe->siglas_materia, collect());
    echo $pe->siglas_materia . ": " . $evs->count() . " evaluaciones encontradas\n";
}
