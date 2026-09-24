<?php

namespace App\Models;

use App\Models\Concerns\PerteneceASucursal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MovimientoInventario extends Model
{
    use HasFactory;

    // RNF07: ligado a una sucursal (la principal por defecto).
    use PerteneceASucursal;

    protected $table = 'movimientos_inventario';

    public const TIPO_INICIAL = 'inicial';

    public const TIPO_COMPRA = 'compra';

    public const TIPO_DEVOLUCION = 'devolucion';

    public const TIPO_AJUSTE_POSITIVO = 'ajuste_positivo';

    public const TIPO_VENTA = 'venta';

    public const TIPO_AJUSTE_NEGATIVO = 'ajuste_negativo';

    public const TIPO_MERMA = 'merma';

    public const TIPOS_ENTRADA = [
        self::TIPO_INICIAL,
        self::TIPO_COMPRA,
        self::TIPO_DEVOLUCION,
        self::TIPO_AJUSTE_POSITIVO,
    ];

    public const TIPOS_SALIDA = [
        self::TIPO_VENTA,
        self::TIPO_AJUSTE_NEGATIVO,
        self::TIPO_MERMA,
    ];

    protected $fillable = [
        'producto_id',
        'user_id',
        'tipo',
        'fecha_movimiento',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'motivo',
        'observaciones',
        'referencia_type',
        'referencia_id',
    ];

    protected static function booted(): void
    {
        // RF02/RF03: si no se indica la fecha de la transacción, es la actual.
        static::creating(function (self $movimiento): void {
            $movimiento->fecha_movimiento ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'fecha_movimiento' => 'datetime',
            'cantidad' => 'integer',
            'stock_anterior' => 'integer',
            'stock_nuevo' => 'integer',
        ];
    }

    public function producto(): BelongsTo
    {
        // Incluye eliminados lógicamente: el historial debe seguir mostrándolos.
        return $this->belongsTo(Producto::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referencia(): MorphTo
    {
        return $this->morphTo();
    }

    public function esEntrada(): bool
    {
        return in_array($this->tipo, self::TIPOS_ENTRADA, true);
    }

    public function esSalida(): bool
    {
        return in_array($this->tipo, self::TIPOS_SALIDA, true);
    }
}
