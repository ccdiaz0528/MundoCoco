<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

/** RF01 + RF10: la gestión del catálogo es del Administrador; el Operario solo consulta. */
class ProductoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver productos');
    }

    public function view(User $user, Producto $producto): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('crear productos');
    }

    public function update(User $user, Producto $producto): bool
    {
        return $user->can('editar productos');
    }

    /** RF01 eliminar: borrado lógico (SoftDeletes) que conserva ventas y trazabilidad RF12. */
    public function delete(User $user, Producto $producto): bool
    {
        return $user->can('eliminar productos');
    }

    public function restore(User $user, Producto $producto): bool
    {
        return $user->can('eliminar productos');
    }

    /** Nunca se borra físicamente: rompería ventas y movimientos históricos. */
    public function forceDelete(User $user, Producto $producto): bool
    {
        return false;
    }
}
