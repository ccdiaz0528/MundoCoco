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
        if ($gasto->caja_id) {
            $caja = Caja::find($gasto->caja_id);
            if ($caja && $caja->estado === 'cerrada') {
                return false;
            }
        }
        // Si es por fecha cerrada, tampoco
        if (Caja::whereDate('fecha', $gasto->fecha)->where('estado', 'cerrada')->exists()) {
            return false;
        }

        return $user->hasRole(['Admin', 'Operador']);
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
