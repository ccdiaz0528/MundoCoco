<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

/** RNF04: la auditoría es de solo lectura. */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver auditoria');
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $log): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $log): bool
    {
        return false;
    }
}
