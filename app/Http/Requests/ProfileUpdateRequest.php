<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas para actualizar el perfil de un usuario de prueba2.
     * Se permite editar nombre, email y estado si aplica.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('Usuario', 'email')->ignore($this->user()->getAuthIdentifier(), 'id_usuario'),
            ],
            'estado' => ['sometimes', 'in:activo,inactivo'],
        ];
    }
}
