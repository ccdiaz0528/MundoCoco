<?php

use App\Models\MetodoPago;
use App\Models\Venta;

describe('MetodoPago', function () {
    it('puede crear un método de pago', function () {
        $metodoPago = MetodoPago::factory()->create([
            'nombre' => 'Tarjeta Crédito',
        ]);

        expect($metodoPago->nombre)->toBe('Tarjeta Crédito');
    });

    it('puede tener muchas ventas', function () {
        $metodoPago = MetodoPago::factory()->create();
        Venta::factory(4)->create(['metodo_pago_id' => $metodoPago->id]);

        expect($metodoPago->ventas()->count())->toBe(4);
    });

    it('solo muestra métodos activos', function () {
        MetodoPago::factory()->create(['activo' => false]);
        MetodoPago::factory(2)->create(['activo' => true]);

        $metodosActivos = MetodoPago::where('activo', true)->get();

        expect($metodosActivos->count())->toBe(2);
    });

    it('reconoce los métodos estándar de pago', function () {
        $metodos = ['Efectivo', 'Transferencia', 'Tarjeta'];
        foreach ($metodos as $nombre) {
            $metodoPago = MetodoPago::factory()->create(['nombre' => $nombre]);
            expect($metodoPago->nombre)->toBe($nombre);
        }
    });
});
