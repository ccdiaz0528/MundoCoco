<?php

namespace App\Models;

use App\Models\Concerns\PerteneceASucursal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    // RNF07: ligado a una sucursal (la principal por defecto).
    use PerteneceASucursal;

    protected $table = 'ventas';

    protected $fillable = [
        'metodo_pago_id',
        'total',
        'observaciones',
        'fecha_venta',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'anulada_at' => 'datetime',
        'total' => 'decimal:2',
    ];

    // Una venta pertenece a un método de pago
    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class);
    }

    // Una venta tiene muchos detalles (productos)
    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    public function anulada(): bool
    {
        return $this->anulada_at !== null;
    }

    /** Ventas que cuentan en caja y reportes (no anuladas). */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('anulada_at'));
    }
}
