<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Estudiante;
use App\Models\Materia;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InscripcionController extends Controller
{
    public function index()
    {
        $inscripciones = Inscripcion::with(['estudiante', 'materia'])->latest()->get();
        return response()->json(['success' => true, 'data' => $inscripciones]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'estudiante_id'     => 'required|exists:estudiantes,id',
            'materia_id'        => 'required|exists:materias,id',
            'periodo'           => 'required|string',
            'fecha_inscripcion' => 'required|date',
            'estado'            => 'required|string',
        ]);

        $estudiante = Estudiante::findOrFail($request->estudiante_id);
        
        // Validación de duplicados
        $existe = Inscripcion::where('estudiante_id', $request->estudiante_id)
            ->where('materia_id', $request->materia_id)
            ->where('periodo', $request->periodo)
            ->exists();

        if ($existe) {
            return response()->json(['error' => "Esta materia ya está inscrita para este periodo."], 422);
        }

        // Validación de créditos
        $creditosActuales = Inscripcion::where('estudiante_id', $request->estudiante_id)
            ->where('periodo', $request->periodo)
            ->with('materia')
            ->get()
            ->sum(fn($ins) => $ins->materia->creditos ?? 0);

        $nuevaMateria = Materia::find($request->materia_id);
        if (($creditosActuales + ($nuevaMateria->creditos ?? 0)) > 25) {
            return response()->json(['error' => "Límite de créditos excedido ({$creditosActuales}/25)."], 422);
        }

        // Guardado manual para asegurar tipos
        $inscripcion = new Inscripcion();
        $inscripcion->estudiante_id = $request->estudiante_id;
        $inscripcion->materia_id    = $request->materia_id;
        $inscripcion->periodo       = $request->periodo;
        $inscripcion->fecha_inscripcion = $request->fecha_inscripcion;
        $inscripcion->estado        = strtolower(trim($request->estado));
        $inscripcion->save();

        return response()->json(['message' => 'Creado con éxito', 'data' => $inscripcion], 201);
    }

    public function update(Request $request, $id)
    {
        // 1. Buscamos el registro
        $inscripcion = Inscripcion::findOrFail($id);
        
        // 2. Validamos (importante para que Laravel reconozca los tipos)
        $request->validate([
            'estudiante_id'     => 'required|numeric',
            'materia_id'        => 'required|numeric',
            'periodo'           => 'required|string',
            'fecha_inscripcion' => 'required|date',
            'estado'            => 'required|string',
        ]);

        // 3. ASIGNACIÓN EXPLÍCITA (Solución al error 1265)
        // Forzamos el valor a ser un string limpio.
        $inscripcion->estudiante_id     = (int) $request->estudiante_id;
        $inscripcion->materia_id        = (int) $request->materia_id;
        $inscripcion->periodo           = (string) $request->periodo;
        $inscripcion->fecha_inscripcion = $request->fecha_inscripcion;
        
        // Saneamiento de 'estado'
        $valorEstado = strtolower(trim($request->estado));
        // Si el valor no es 'activa', por defecto será 'inactiva' para cumplir con ENUM si existe
        $inscripcion->estado = ($valorEstado === 'activa') ? 'activa' : 'inactiva';

        // 4. Guardar usando save() en lugar de update()
        $inscripcion->save();

        return response()->json([
            'success' => true,
            'data' => $inscripcion->load(['estudiante', 'materia'])
        ]);
    }

    public function destroy($id)
    {
        Inscripcion::destroy($id);
        return response()->json(['message' => 'Eliminado']);
    }

    public function getFormData()
    {
        return response()->json([
            'estudiantes' => Estudiante::where('estado', 'activo')->get(),
            'materias' => Materia::where('estado', 'activa')->get()
        ]);
    }

    public function reportePDF(Request $request)
    {
        $grado = $request->query('grado');
        $seccion = $request->query('seccion');

        $query = Inscripcion::with(['estudiante', 'materia']);

        if ($grado) {
            $query->whereHas('estudiante', fn($q) => $q->where('grado', $grado));
        }
        if ($seccion) {
            $query->whereHas('estudiante', fn($q) => $q->where('seccion', $seccion));
        }

        $data = [
            'titulo' => 'Reporte de Inscripciones',
            'fecha' => date('d/m/Y'),
            'inscripciones' => $query->get(),
            'filtros' => ['grado' => $grado ?? 'Todos', 'seccion' => $seccion ?? 'Todas']
        ];

        return Pdf::loadView('pdf.reporte_inscripciones', $data)->stream("Reporte.pdf");
    }
}