<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    // Un método de pago aparece en muchas ventas
    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}
