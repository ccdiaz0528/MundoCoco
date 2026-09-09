<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\User;

class CajaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin', 'Operador', 'Consultor']);
    }

    public function view(User $user, Caja $caja): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Admin', 'Operador']);
    }

    public function update(User $user, Caja $caja): bool
    {
        return $user->hasRole(['Admin', 'Operador']) && $caja->estado === 'abierta';
    }

    public function delete(User $user, Caja $caja): bool
    {
        return false;
    }
}
