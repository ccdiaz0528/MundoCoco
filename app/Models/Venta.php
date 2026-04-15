<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'metodo_pago_id',
        'total',
        'observaciones',
        'fecha_venta',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
    ];

    // Una venta pertenece a un método de pago
    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class);
    }

    // Una venta tiene muchos detalles (productos)
    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }
}
