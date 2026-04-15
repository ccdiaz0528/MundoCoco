<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $table = 'caja';

    protected $fillable = [
        'fecha',
        'saldo_inicial',
        'total_efectivo',
        'total_transferencias',
        'total_tarjetas',
        'total_ventas',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];
}
