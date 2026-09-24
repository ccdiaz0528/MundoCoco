<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\Gasto;
use App\Models\User;

class GastoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin', 'Operador', 'Consultor']);
    }

    public function view(User $user, Gasto $gasto): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Admin', 'Operador']);
    }

    public function update(User $user, Gasto $gasto): bool
    {
        // No editar gastos de cajas cerradas
        return $user->hasRole(['Admin', 'Operador']) && ! $this->esDeCajaCerrada($gasto);
    }

    public function delete(User $user, Gasto $gasto): bool
    {
        return $user->hasRole(['Admin']) && ! $this->esDeCajaCerrada($gasto);
    }

    private function esDeCajaCerrada(Gasto $gasto): bool
    {
        if ($gasto->caja_id) {
            return Caja::whereKey($gasto->caja_id)->where('estado', 'cerrada')->exists();
        }

        return Caja::whereDate('fecha', $gasto->fecha)->where('estado', 'cerrada')->exists();
    }
}
