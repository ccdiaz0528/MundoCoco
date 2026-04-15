<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $table = 'caja';

    protected $fillable = [
        'fecha',
        'estado',
        'saldo_inicial',
        'total_efectivo',
        'total_transferencias',
        'total_tarjetas',
        'total_ventas',
        'saldo_real',
        'diferencia',
        'fecha_cierre',
        'observaciones',
        'observaciones_cierre',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_cierre' => 'datetime',
        'saldo_inicial' => 'decimal:2',
        'total_efectivo' => 'decimal:2',
        'total_transferencias' => 'decimal:2',
        'total_tarjetas' => 'decimal:2',
        'total_ventas' => 'decimal:2',
        'saldo_real' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];
}
