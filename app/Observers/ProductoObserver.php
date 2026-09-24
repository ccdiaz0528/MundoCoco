<?php

namespace App\Observers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

class ProductoObserver
{
    /**
     * Stock antes de un ajuste manual. Estático porque Laravel resuelve una
     * instancia distinta del observer por cada evento (Class@event), así que
     * el estado de instancia no sobrevive de updating a updated.
     * Se guarda aquí (no en atributos del modelo) para no contaminar
     * el UPDATE con columnas inexistentes.
     *
     * @var array<int, array{anterior: int, nuevo: int}>
     */
    private static array $ajustes = [];

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
        // Si se edita stock_actual manualmente fuera de movimientos, registrar ajuste.
        // Se excluye solo el módulo de movimientos, cuyos servicios ya crean
        // su propio MovimientoInventario. Los seeders usan WithoutModelEvents,
        // así que no necesitan exclusión adicional (y en consola/tinker los
        // ajustes también deben quedar trazados).
        if ($producto->isDirty('stock_actual') && ! request()->routeIs('*.movimientos.*')) {
            self::$ajustes[$producto->id] = [
                'anterior' => $producto->getOriginal('stock_actual'),
                'nuevo' => $producto->stock_actual,
            ];
        }
    }

    public function updated(Producto $producto): void
    {
        $ajuste = self::$ajustes[$producto->id] ?? null;
        unset(self::$ajustes[$producto->id]);

        if ($ajuste === null || $ajuste['nuevo'] === $ajuste['anterior']) {
            return;
        }

        $diff = $ajuste['nuevo'] - $ajuste['anterior'];

        MovimientoInventario::create([
            'producto_id' => $producto->id,
            'user_id' => Auth::id(),
            'tipo' => $diff > 0 ? MovimientoInventario::TIPO_AJUSTE_POSITIVO : MovimientoInventario::TIPO_AJUSTE_NEGATIVO,
            'cantidad' => abs($diff),
            'stock_anterior' => $ajuste['anterior'],
            'stock_nuevo' => $ajuste['nuevo'],
            'motivo' => 'Ajuste manual de stock',
        ]);

        // RNF04: auditoría de cambios de inventario.
        AuditService::logStockAjuste($producto, $ajuste['anterior'], $ajuste['nuevo'], $diff > 0 ? MovimientoInventario::TIPO_AJUSTE_POSITIVO : MovimientoInventario::TIPO_AJUSTE_NEGATIVO);
    }
}
