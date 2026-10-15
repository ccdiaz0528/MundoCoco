<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Producto;
use Illuminate\Support\Collection;

class ProductoController
{
    /** Catálogo activo con su stock (RNF07, solo lectura). */
    public function index(): Collection
    {
        return Producto::query()
            ->with('categoria')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Producto $p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria?->nombre,
                'precio_venta' => (string) $p->precio_venta,
                'stock_actual' => $p->stock_actual,
                'stock_minimo' => $p->stock_minimo,
                'stock_bajo' => $p->stockBajo(),
            ]);
    }
}
