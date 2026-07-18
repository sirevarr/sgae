<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnioEscolar;
use App\Models\Estudiante;
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

        $estudiantesCount   = Matricula::activa()->count();
        $docentesCount      = Personal::where('cargo', 'like', '%docente%')->orWhereHas('docente')->count();
        $seccionesCount     = $anioVigente
            ? Seccion::where('codigo_ano_escolar', $anioVigente->codigo_ano_escolar)->count()
            : 0;
        $materiasCount      = Materia::count();

        // Promedio general de todas las evaluaciones del año vigente
        $promedioGlobal = 0;
        $porcentajeAprobados = 0;
        $notaMinima = \App\Models\ParametroSistema::notaMinima();

        if ($anioVigente) {
            $evaluaciones = Evaluacion::where('codigo_ano_escolar', $anioVigente->codigo_ano_escolar)
                ->whereNotNull('nota')
                ->get();

            if ($evaluaciones->count() > 0) {
                $promedioGlobal      = round($evaluaciones->avg('nota'), 2);
                $aprobados           = $evaluaciones->where('nota', '>=', $notaMinima)->count();
                $porcentajeAprobados = round(($aprobados / $evaluaciones->count()) * 100, 1);
            }
        }

        // Momentos evaluativos activos
        $momentoActual = $anioVigente
            ? MomentoEvaluativo::where('codigo_ano_escolar', $anioVigente->codigo_ano_escolar)
                ->where('estado', 'activo')
                ->first()
            : null;

        return response()->json([
            'estudiantesCount'   => $estudiantesCount,
            'docentesCount'      => $docentesCount,
            'seccionesCount'     => $seccionesCount,
            'materiasCount'      => $materiasCount,
            'promedioGlobal'     => $promedioGlobal,
            'porcentajeAprobados' => $porcentajeAprobados,
            'anioVigente'        => $anioVigente?->codigo_ano_escolar ?? 'Sin año vigente',
            'momentoActual'      => $momentoActual?->nombre ?? 'Sin momento activo',
        ]);
    }
}
