<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\User;

/** RF06 + RF10. */
class CategoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver categorias');
    }

    public function view(User $user, Categoria $categoria): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('crear categorias');
    }

    public function update(User $user, Categoria $categoria): bool
    {
        return $user->can('editar categorias');
    }

    /** Solo categorías sin productos (ni eliminados lógicamente), para no dejar huérfanos. */
    public function delete(User $user, Categoria $categoria): bool
    {
        return $user->can('eliminar categorias') && ! $categoria->productos()->withTrashed()->exists();
    }

    public function restore(User $user, Categoria $categoria): bool
    {
        return false;
    }

    public function forceDelete(User $user, Categoria $categoria): bool
    {
        return false;
    }
}
