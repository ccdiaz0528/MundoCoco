<?php

namespace App\Policies;

use App\Models\MovimientoInventario;
use App\Models\User;

class MovimientoInventarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin', 'Operador', 'Consultor']);
    }

    public function view(User $user, MovimientoInventario $movimiento): bool
    {
        return $user->hasRole(['Admin', 'Operador', 'Consultor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Admin', 'Operador']);
    }

    public function update(User $user, MovimientoInventario $movimiento): bool
    {
        return false; // trazabilidad inmutable
    }

    public function delete(User $user, MovimientoInventario $movimiento): bool
    {
        return false;
    }
}
