<?php

use App\Models\MovimientoInventario;
use App\Models\Producto;

describe('ProductoObserver ajuste manual', function () {
    it('registra ajuste positivo al subir stock con update normal', function () {
        $producto = Producto::factory()->create(['stock_actual' => 10, 'stock_minimo' => 5]);

        $producto->update(['stock_actual' => 15]);

        $movimiento = MovimientoInventario::where('producto_id', $producto->id)
            ->where('tipo', MovimientoInventario::TIPO_AJUSTE_POSITIVO)
            ->first();

        expect($movimiento)->not->toBeNull()
            ->and($movimiento->cantidad)->toBe(5)
            ->and($movimiento->stock_anterior)->toBe(10)
            ->and($movimiento->stock_nuevo)->toBe(15);
    });

    it('registra ajuste negativo al bajar stock con update normal', function () {
        $producto = Producto::factory()->create(['stock_actual' => 10, 'stock_minimo' => 5]);

        $producto->update(['stock_actual' => 7]);

        $movimiento = MovimientoInventario::where('producto_id', $producto->id)
            ->where('tipo', MovimientoInventario::TIPO_AJUSTE_NEGATIVO)
            ->first();

        expect($movimiento)->not->toBeNull()
            ->and($movimiento->cantidad)->toBe(3);
    });

    it('no registra movimiento al editar otros campos', function () {
        $producto = Producto::factory()->create(['stock_actual' => 10]);
        $antes = MovimientoInventario::where('producto_id', $producto->id)->count();

        $producto->update(['nombre' => 'Nuevo nombre']);

        expect(MovimientoInventario::where('producto_id', $producto->id)->count())->toBe($antes);
    });
});
