<?php

namespace App\Policies;

use App\Models\User;

/** RF10: crear usuarios, asignar roles y cambiar contraseñas (Administrador). */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver usuarios');
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('gestionar usuarios');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('gestionar usuarios');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('gestionar usuarios') && $user->isNot($model);
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
