<?php

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Services\VentaService;
use Illuminate\Validation\ValidationException;

describe('VentaService', function () {
    it('guarda el precio confiable y descuenta inventario', function () {
        $metodoPago = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $producto = Producto::factory()->create([
            'categoria_id' => Categoria::factory(),
            'precio_venta' => 12.50,
            'stock_actual' => 5,
        ]);

        $venta = app(VentaService::class)->crear([
            'metodo_pago_id' => $metodoPago->id,
            'observaciones' => 'Venta de prueba',
        ], [
            ['producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => 1, 'subtotal' => 2],
        ]);

        expect((float) $venta->total)->toBe(25.0)
            ->and((float) $venta->detalles->first()->precio_unitario)->toBe(12.5)
            ->and((float) $venta->detalles->first()->subtotal)->toBe(25.0)
            ->and($producto->fresh()->stock_actual)->toBe(3);
    });

    it('rechaza el total acumulado cuando excede el stock', function () {
        $metodoPago = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $producto = Producto::factory()->create(['stock_actual' => 3]);

        expect(fn () => app(VentaService::class)->crear([
            'metodo_pago_id' => $metodoPago->id,
        ], [
            ['producto_id' => $producto->id, 'cantidad' => 2],
            ['producto_id' => $producto->id, 'cantidad' => 2],
        ]))->toThrow(ValidationException::class);

        expect($producto->fresh()->stock_actual)->toBe(3);
    });

    it('rechaza ventas cuando la caja de esa fecha está cerrada', function () {
        $metodoPago = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $producto = Producto::factory()->create(['stock_actual' => 5]);
        Caja::factory()->create(['fecha' => now()->toDateString(), 'estado' => 'cerrada']);

        expect(fn () => app(VentaService::class)->crear([
            'metodo_pago_id' => $metodoPago->id,
        ], [
            ['producto_id' => $producto->id, 'cantidad' => 1],
        ]))->toThrow(ValidationException::class);

        expect($producto->fresh()->stock_actual)->toBe(5);
    });
});
