<?php

namespace App\Models;

use App\Models\Concerns\PerteneceASucursal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    use HasFactory;

    // RNF07: ligado a una sucursal (la principal por defecto).
    use PerteneceASucursal;

    protected $table = 'caja';

    protected $fillable = [
        'fecha',
        'estado',
        'saldo_inicial',
        'total_efectivo',
        'total_nequi',
        'total_transferencias',
        'total_tarjetas',
        'total_ventas',
        'total_gastos',
        'saldo_teorico',
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
        'total_nequi' => 'decimal:2',
        'total_transferencias' => 'decimal:2',
        'total_tarjetas' => 'decimal:2',
        'total_ventas' => 'decimal:2',
        'total_gastos' => 'decimal:2',
        'saldo_teorico' => 'decimal:2',
        'saldo_real' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'caja_id');
    }
}
