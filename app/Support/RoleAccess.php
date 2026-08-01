<?php

namespace App\Support;

class RoleAccess
{
    public static function canEvaluate(?object $user): bool
    {
        return self::isAdmin($user) || self::isControlEstudios($user) || self::isDocente($user);
    }

    public static function canManageRecords(?object $user): bool
    {
        return self::isAdmin($user) || self::isControlEstudios($user);
    }

    public static function isAdmin(?object $user): bool
    {
        return self::hasRole($user, 'administrador');
    }

    public static function isControlEstudios(?object $user): bool
    {
        return self::hasRole($user, ['administrador', 'control_estudios']);
    }

    public static function isDocente(?object $user): bool
    {
        return self::hasRole($user, 'docente');
    }

    private static function hasRole(?object $user, array|string $roles): bool
    {
        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($roles);
        }

        $userRole = strtolower(trim((string) ($user->rol ?? '')));
        if ($userRole === 'administrador') {
            return true;
        }

        $roleList = is_array($roles) ? $roles : explode(',', $roles);
        foreach ($roleList as $role) {
            if ($userRole === strtolower(trim($role))) {
                return true;
            }
        }

        return false;
    }
}
