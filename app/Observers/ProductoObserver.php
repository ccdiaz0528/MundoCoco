<?php

namespace App\Observers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Support\Facades\Auth;

class ProductoObserver
{
    public function created(Producto $producto): void
    {
        // RF02: Al crear producto con stock inicial, registrar trazabilidad
        if ($producto->stock_actual > 0) {
            MovimientoInventario::create([
                'producto_id' => $producto->id,
                'user_id' => Auth::id(),
                'tipo' => MovimientoInventario::TIPO_INICIAL,
                'cantidad' => $producto->stock_actual,
                'stock_anterior' => 0,
                'stock_nuevo' => $producto->stock_actual,
                'motivo' => 'Inventario inicial al crear producto',
                'observaciones' => $producto->descripcion,
            ]);
        }
    }

    public function updating(Producto $producto): void
    {
        // Si se edita stock_actual manualmente fuera de movimientos, registrar ajuste
        if ($producto->isDirty('stock_actual')) {
            $anterior = $producto->getOriginal('stock_actual');
            $nuevo = $producto->stock_actual;
            $diff = $nuevo - $anterior;

            if ($diff !== 0 && ! app()->runningInConsole() && ! request()->routeIs('*.movimientos.*')) {
                // Evitar duplicar cuando viene de InventarioService que ya crea movimiento
                // Solo si no estamos dentro de transacción de InventarioService
                $tipo = $diff > 0 ? MovimientoInventario::TIPO_AJUSTE_POSITIVO : MovimientoInventario::TIPO_AJUSTE_NEGATIVO;

                // Se creará en updated para tener ID
                $producto->setAttribute('_movimiento_diff', $diff);
                $producto->setAttribute('_movimiento_anterior', $anterior);
                $producto->setAttribute('_movimiento_nuevo', $nuevo);
                $producto->setAttribute('_movimiento_tipo', $tipo);
            }
        }
    }

    public function updated(Producto $producto): void
    {
        if ($producto->hasAttribute('_movimiento_diff')) {
            $diff = $producto->getAttribute('_movimiento_diff');
            MovimientoInventario::create([
                'producto_id' => $producto->id,
                'user_id' => Auth::id(),
                'tipo' => $producto->getAttribute('_movimiento_tipo'),
                'cantidad' => abs($diff),
                'stock_anterior' => $producto->getAttribute('_movimiento_anterior'),
                'stock_nuevo' => $producto->getAttribute('_movimiento_nuevo'),
                'motivo' => 'Ajuste manual de stock',
            ]);
        }
    }
}
