<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\Gasto;
use App\Models\User;

/** RF11 + RF10: gastos del día a cargo del Administrador; bloqueados con caja cerrada. */
class GastoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver gastos');
    }

    public function view(User $user, Gasto $gasto): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('crear gastos');
    }

    public function update(User $user, Gasto $gasto): bool
    {
        return $user->can('editar gastos') && ! $this->esDeCajaCerrada($gasto);
    }

    public function delete(User $user, Gasto $gasto): bool
    {
        return $user->can('eliminar gastos') && ! $this->esDeCajaCerrada($gasto);
    }

    private function esDeCajaCerrada(Gasto $gasto): bool
    {
        if ($gasto->caja_id) {
            return Caja::whereKey($gasto->caja_id)->where('estado', 'cerrada')->exists();
        }

        return Caja::whereDate('fecha', $gasto->fecha)->where('estado', 'cerrada')->exists();
    }
}
