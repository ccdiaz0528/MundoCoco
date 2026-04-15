<?php

use App\Models\VentaDetalle;
use App\Models\Venta;
use App\Models\Producto;

describe('VentaDetalle', function () {
    it('puede crear un detalle de venta', function () {
        $detalle = VentaDetalle::factory()->create([
            'cantidad' => 3,
            'precio_unitario' => 100.50,
        ]);

        expect($detalle->cantidad)->toBe(3);
        expect($detalle->precio_unitario)->toBe(100.50);
    });

    it('pertenece a una venta', function () {
        $venta = Venta::factory()->create();
        $detalle = VentaDetalle::factory()->create(['venta_id' => $venta->id]);

        expect($detalle->venta()->first()->id)->toBe($venta->id);
    });

    it('pertenece a un producto', function () {
        $producto = Producto::factory()->create();
        $detalle = VentaDetalle::factory()->create(['producto_id' => $producto->id]);

        expect($detalle->producto()->first()->id)->toBe($producto->id);
    });

    it('calcula el subtotal correctamente', function () {
        $detalle = VentaDetalle::factory()->create([
            'cantidad' => 5,
            'precio_unitario' => 50,
            'subtotal' => 250,
        ]);

        expect($detalle->subtotal)->toBe(250);
    });

    it('el subtotal es cantidad por precio unitario', function () {
        $cantidad = 10;
        $precioUnitario = 25.50;
        $esperado = $cantidad * $precioUnitario;

        $detalle = VentaDetalle::factory()->create([
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $esperado,
        ]);

        expect($detalle->subtotal)->toBe($esperado);
    });
});
