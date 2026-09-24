<?php

namespace App\Policies;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver roles');
    }

    public function view(User $user, Role $role): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('gestionar roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('gestionar roles');
    }

    /** Los tres roles de RF10 no se pueden borrar. */
    public function delete(User $user, Role $role): bool
    {
        return $user->can('gestionar roles')
            && ! in_array($role->name, [RoleSeeder::ADMIN, RoleSeeder::OPERARIO, RoleSeeder::CONSULTOR], true);
    }

    public function restore(User $user, Role $role): bool
    {
        return false;
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return false;
    }
}
