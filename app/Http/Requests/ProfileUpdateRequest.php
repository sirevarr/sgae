<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Reglas para actualizar el perfil de un usuario de prueba2.
     * Solo se permite editar el estado (no el codigo_usuario ni el rol).
     */
    public function rules(): array
    {
        return [
            // codigo_usuario es de solo lectura — no se puede cambiar desde el perfil
            'estado' => ['sometimes', 'in:activo,inactivo'],
        ];
    }
}
