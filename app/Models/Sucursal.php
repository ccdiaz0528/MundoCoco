<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * RNF07: punto de venta. Hoy solo existe la principal; el modelo deja lista
 * la expansión a varias sucursales sin migrar datos.
 */
class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = ['nombre', 'direccion', 'principal', 'activa'];

    protected function casts(): array
    {
        return ['principal' => 'boolean', 'activa' => 'boolean'];
    }

    /** Sucursal por defecto de las operaciones (se crea si no existe). */
    public static function principal(): self
    {
        return static::query()->where('principal', true)->first()
            ?? static::query()->create(['nombre' => 'MundoCoco Principal', 'direccion' => 'Cali, Colombia', 'principal' => true, 'activa' => true]);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}
