<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MateriaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->query('buscar');
        $materias = Materia::when($buscar, function ($query, $buscar) {
            return $query->where('nombre', 'like', "%{$buscar}%")
                         ->orWhere('codigo_materia', 'like', "%{$buscar}%");
        })->get();

        return response()->json(['success' => true, 'data' => $materias]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codigo_materia' => 'required|string|max:20|unique:materias',
            'nombre' => 'required|string|max:150',
            'creditos' => 'required|integer|min:1',
            'estado' => 'required|in:activa,inactiva'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $materia = Materia::create($request->all());
        return response()->json(['success' => true, 'data' => $materia], 201);
    }
    
    // Aquí puedes añadir update() y destroy() siguiendo la misma lógica
}