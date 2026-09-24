<?php

namespace App\Models\Concerns;

use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RNF07: toda operación queda ligada a una sucursal; si no se indica, a la
 * principal. Así una segunda sucursal no exige cambiar los registros existentes.
 */
trait PerteneceASucursal
{
    public static function bootPerteneceASucursal(): void
    {
        static::creating(function ($modelo): void {
            $modelo->sucursal_id ??= Sucursal::principal()->id;
        });
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}
