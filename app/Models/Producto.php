<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'codigo',
        'nombre',
        'descripcion',
        'precio_venta',
        'precio_costo',
        'stock_actual',
        'stock_minimo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio_venta' => 'decimal:2',
            'precio_costo' => 'decimal:2',
            'stock_actual' => 'integer',
            'stock_minimo' => 'integer',
            'activo' => 'boolean',
        ];
    }

    // RF01: todo producto del catálogo tiene un código único.
    protected static function booted(): void
    {
        static::creating(function (Producto $producto): void {
            if (empty($producto->codigo)) {
                $producto->codigo = static::generarCodigo();
            }
        });
    }

    /**
     * Genera un código único P-XXXXXX reintentando ante colisiones.
     */
    public static function generarCodigo(): string
    {
        for ($intento = 0; $intento < 5; $intento++) {
            $codigo = 'P-'.strtoupper(Str::random(6));
            if (! static::where('codigo', $codigo)->exists()) {
                return $codigo;
            }
        }

        // Reserva prácticamente imposible de colisionar.
        return 'P-'.strtoupper(Str::random(6)).time();
    }

    // Un producto pertenece a una categoría
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    // Un producto aparece en muchos detalles de venta
    public function ventaDetalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class);
    }

    // Método útil: saber si el stock está bajo
    public function stockBajo(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }
}
