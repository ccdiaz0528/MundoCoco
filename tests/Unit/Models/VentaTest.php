<?php

use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\MetodoPago;
use App\Models\Producto;

describe('Venta', function () {
    it('puede crear una venta', function () {
        $metodoPago = MetodoPago::factory()->create(['nombre' => 'Efectivo']);
        $venta = Venta::factory()->create([
            'metodo_pago_id' => $metodoPago->id,
            'total' => 250.50,
        ]);

        expect($venta->total)->toBe(250.50);
        expect($venta->metodoPago->nombre)->toBe('Efectivo');
    });

    it('pertenece a un método de pago', function () {
        $metodoPago = MetodoPago::factory()->create();
        $venta = Venta::factory()->create(['metodo_pago_id' => $metodoPago->id]);

        expect($venta->metodoPago->id)->toBe($metodoPago->id);
    });

    it('puede tener muchos detalles de venta', function () {
        $venta = Venta::factory()->create();
        VentaDetalle::factory(3)->create(['venta_id' => $venta->id]);

        expect($venta->detalles()->count())->toBe(3);
    });

    it('la suma de detalles debe coincidir con el total', function () {
        $venta = Venta::factory()->create();
        VentaDetalle::factory()->create(['venta_id' => $venta->id, 'subtotal' => 100]);
        VentaDetalle::factory()->create(['venta_id' => $venta->id, 'subtotal' => 150]);

        $totalDetalles = $venta->detalles()->sum('subtotal');

        expect($totalDetalles)->toBe(250);
    });

    it('puede tener observaciones', function () {
        $venta = Venta::factory()->create(['observaciones' => 'Cliente frecuente']);

        expect($venta->observaciones)->toBe('Cliente frecuente');
    });

    it('tiene fecha de venta', function () {
        $venta = Venta::factory()->create();

        expect($venta->fecha_venta)->not()->toBeNull();
    });
});
