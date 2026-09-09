<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaDetalle extends Model
{
    use HasFactory;

    protected $table = 'venta_detalles';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    // Un detalle pertenece a una venta
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    // Un detalle pertenece a un producto
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
