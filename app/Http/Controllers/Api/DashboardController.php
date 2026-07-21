<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnioEscolar;
use App\Models\Evaluacion;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\MomentoEvaluativo;
use App\Models\Personal;
use App\Models\Seccion;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $anioVigente = AnioEscolar::vigente();
        $codigoAnio = $anioVigente?->codigo_ano_escolar;

        $estudiantesCount = Matricula::activa()->count();
        $docentesCount = Personal::whereHas('docente')->count();
        $seccionesCount = $codigoAnio ? Seccion::where('codigo_ano_escolar', $codigoAnio)->count() : 0;
        $materiasCount = Materia::count();

        $estadisticasEvaluacion = $this->estadisticasEvaluacion($codigoAnio);
        $momentoActual = $this->momentoActual($codigoAnio);

        return response()->json([
            'estudiantesCount' => $estudiantesCount,
            'docentesCount' => $docentesCount,
            'seccionesCount' => $seccionesCount,
            'materiasCount' => $materiasCount,
            'promedioGlobal' => $estadisticasEvaluacion['promedioGlobal'],
            'porcentajeAprobados' => $estadisticasEvaluacion['porcentajeAprobados'],
            'anioVigente' => $codigoAnio ?? 'Sin año vigente',
            'momentoActual' => $momentoActual?->nombre ?? 'Sin momento activo',
        ]);
    }

    private function estadisticasEvaluacion(?string $codigoAnio): array
    {
        if ($codigoAnio === null) {
            return ['promedioGlobal' => 0, 'porcentajeAprobados' => 0];
        }

        $evaluaciones = Evaluacion::where('codigo_ano_escolar', $codigoAnio)
            ->whereNotNull('nota')
            ->get();

        if ($evaluaciones->isEmpty()) {
            return ['promedioGlobal' => 0, 'porcentajeAprobados' => 0];
        }

        $notaMinima = \App\Models\ParametroSistema::notaMinima();
        $aprobados = $evaluaciones->where('nota', '>=', $notaMinima)->count();

        return [
            'promedioGlobal' => round($evaluaciones->avg('nota'), 2),
            'porcentajeAprobados' => round(($aprobados / $evaluaciones->count()) * 100, 1),
        ];
    }

    private function momentoActual(?string $codigoAnio): ?MomentoEvaluativo
    {
        if ($codigoAnio === null) {
            return null;
        }

        return MomentoEvaluativo::where('codigo_ano_escolar', $codigoAnio)
            ->where('estado', 'activo')
            ->first();
    }
}
