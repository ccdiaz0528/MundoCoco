<?php

namespace App\Policies;

use App\Models\MetodoPago;
use App\Models\User;

class MetodoPagoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver metodos_pago');
    }

    public function view(User $user, MetodoPago $metodoPago): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('gestionar metodos_pago');
    }

    public function update(User $user, MetodoPago $metodoPago): bool
    {
        return $user->can('gestionar metodos_pago');
    }

    public function delete(User $user, MetodoPago $metodoPago): bool
    {
        return $user->can('gestionar metodos_pago') && ! $metodoPago->ventas()->exists();
    }

    public function restore(User $user, MetodoPago $metodoPago): bool
    {
        return false;
    }

    public function forceDelete(User $user, MetodoPago $metodoPago): bool
    {
        return false;
    }
}
