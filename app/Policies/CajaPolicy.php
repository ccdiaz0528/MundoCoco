<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\User;

/** RF11 + RF10: el cuadre de caja es del Administrador. */
class CajaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver caja');
    }

    public function view(User $user, Caja $caja): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('crear caja');
    }

    public function update(User $user, Caja $caja): bool
    {
        return $user->can('editar caja') && $caja->estado === 'abierta';
    }

    public function cerrar(User $user, Caja $caja): bool
    {
        return $user->can('cerrar caja') && $caja->estado === 'abierta';
    }

    public function delete(User $user, Caja $caja): bool
    {
        return false;
    }
}
