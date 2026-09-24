<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\User;
use App\Models\Venta;

/** RF04 + RF10: el Operario registra y corrige ventas; nunca se borran (se anulan). */
class VentaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver ventas');
    }

    public function view(User $user, Venta $venta): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('crear ventas');
    }

    public function update(User $user, Venta $venta): bool
    {
        return $user->can('editar ventas') && ! $venta->anulada() && ! $this->cajaCerrada($venta);
    }

    public function anular(User $user, Venta $venta): bool
    {
        return $user->can('anular ventas') && ! $venta->anulada() && ! $this->cajaCerrada($venta);
    }

    public function delete(User $user, Venta $venta): bool
    {
        return false;
    }

    private function cajaCerrada(Venta $venta): bool
    {
        return Caja::query()->whereDate('fecha', $venta->fecha_venta)->where('estado', 'cerrada')->exists();
    }
}
