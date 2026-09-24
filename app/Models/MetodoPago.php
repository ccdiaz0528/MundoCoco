<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    use HasFactory;

    public const EFECTIVO = 'Efectivo';

    // RF04/RF11: Nequi se reporta por separado en la caja.
    public const NEQUI = 'Nequi';

    public const TRANSFERENCIA = 'Transferencia';

    public const TARJETA = 'Tarjeta';

    public const NOMBRES = [self::EFECTIVO, self::NEQUI, self::TRANSFERENCIA, self::TARJETA];

    protected $table = 'metodos_pago';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    // Un método de pago aparece en muchas ventas
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}
