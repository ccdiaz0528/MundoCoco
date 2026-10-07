<?php

namespace App\Models;

use App\Models\Concerns\PerteneceASucursal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    use HasFactory;

    // RNF07: ligado a una sucursal (la principal por defecto).
    use PerteneceASucursal;

    protected $table = 'gastos';

    protected $fillable = [
        'fecha',
        'descripcion',
        'categoria',
        'monto',
        'user_id',
        'caja_id',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    /**
     * Retiro de efectivo (consignación al banco, entrega al dueño): saca
     * dinero de la caja pero no es un gasto del negocio. CajaService lo
     * descuenta del saldo teórico y lo reporta aparte de los gastos.
     */
    public const RETIRO = 'retiro';

    public const CATEGORIAS = [
        'materia_prima' => 'Materia Prima',
        'servicios' => 'Servicios',
        'transporte' => 'Transporte',
        'otros' => 'Otros',
        self::RETIRO => 'Retiro / consignación',
    ];

    /** Solo los gastos del negocio (excluye retiros de efectivo). */
    public function scopeSoloGastos(Builder $query): Builder
    {
        return $query->where('categoria', '!=', self::RETIRO);
    }

    public function scopeSoloRetiros(Builder $query): Builder
    {
        return $query->where('categoria', self::RETIRO);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }
}
