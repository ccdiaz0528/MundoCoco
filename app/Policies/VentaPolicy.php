<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\User;
use App\Models\Venta;

class VentaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin', 'Operador', 'Consultor']);
    }

    public function view(User $user, Venta $venta): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Admin', 'Operador']);
    }

    public function update(User $user, Venta $venta): bool
    {
        return $user->hasRole(['Admin', 'Operador'])
            && ! Caja::query()->whereDate('fecha', $venta->fecha_venta)->where('estado', 'cerrada')->exists();
    }

    public function delete(User $user, Venta $venta): bool
    {
        return false;
    }
}
