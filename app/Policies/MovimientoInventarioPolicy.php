<?php

namespace App\Policies;

use App\Models\MovimientoInventario;
use App\Models\User;

/** RF02/RF03/RF12: historial inmutable; las entradas las registra el Administrador. */
class MovimientoInventarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver movimientos');
    }

    public function view(User $user, MovimientoInventario $movimiento): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('registrar movimientos');
    }

    public function update(User $user, MovimientoInventario $movimiento): bool
    {
        return false;
    }

    public function delete(User $user, MovimientoInventario $movimiento): bool
    {
        return false;
    }
}
