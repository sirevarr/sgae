<?php

namespace App\Models;

/**
 * Alias para compatibilidad con el guard de Laravel.
 * La autenticación real se hace contra la tabla Usuario (prueba2).
 */
class User extends Usuario
{
    protected $table = 'Usuario';
}
