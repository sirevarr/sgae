<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function tableUnavailableResponse(string $tableName): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => "La tabla de {$tableName} no está disponible en la base de datos actual.",
        ], 500);
    }
}
