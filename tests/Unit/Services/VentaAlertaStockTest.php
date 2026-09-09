<?php

use App\Models\MetodoPago;
use App\Models\Producto;
use App\Services\VentaService;

describe('RF04 alerta de stock mínimo al vender', function () {
    it('detecta productos que quedaron bajo el mínimo', function () {
        $bajo = Producto::factory()->create(['stock_actual' => 4, 'stock_minimo' => 5]);
        $sano = Producto::factory()->create(['stock_actual' => 10, 'stock_minimo' => 5]);

        $resultado = app(VentaService::class)->productosBajoMinimo([$bajo->id => 1, $sano->id => 2]);

        expect($resultado->pluck('id')->all())->toBe([$bajo->id]);
    });

    it('detecta el faltante tras una venta real', function () {
        $metodo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO, 'activo' => true]);
        $producto = Producto::factory()->create(['stock_actual' => 5, 'stock_minimo' => 5, 'precio_venta' => 5000]);

        app(VentaService::class)->crear(
            ['metodo_pago_id' => $metodo->id, 'fecha_venta' => now()],
            [['producto_id' => $producto->id, 'cantidad' => 1]]
        );

        $resultado = app(VentaService::class)->productosBajoMinimo([$producto->id => 1]);

        expect($resultado->pluck('id')->all())->toBe([$producto->id]);
    });

    it('retorna vacío cuando nadie quedó bajo el mínimo', function () {
        $producto = Producto::factory()->create(['stock_actual' => 10, 'stock_minimo' => 5]);

        $resultado = app(VentaService::class)->productosBajoMinimo([$producto->id => 2]);

        expect($resultado)->toBeEmpty();
    });

    it('retorna vacío sin cantidades', function () {
        expect(app(VentaService::class)->productosBajoMinimo([]))->toBeEmpty();
    });
});
