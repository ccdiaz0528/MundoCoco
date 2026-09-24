<?php

use App\Models\Caja;
use App\Models\Gasto;

describe('GastoObserver', function () {
    it('recalcula la caja abierta al crear un gasto', function () {
        $fecha = now()->startOfDay();
        $caja = Caja::factory()->create(['fecha' => $fecha, 'estado' => 'abierta', 'total_gastos' => 0]);

        Gasto::factory()->create(['fecha' => $fecha, 'monto' => 5000]);

        expect((float) $caja->refresh()->total_gastos)->toBe(5000.0);
    });

    it('recalcula ambas cajas al cambiar la fecha sin errores', function () {
        $fechaA = now()->startOfDay();
        $fechaB = now()->startOfDay()->addDay();
        $cajaA = Caja::factory()->create(['fecha' => $fechaA, 'estado' => 'abierta', 'total_gastos' => 0]);
        Caja::factory()->create(['fecha' => $fechaB, 'estado' => 'abierta', 'total_gastos' => 0]);

        $gasto = Gasto::factory()->create(['fecha' => $fechaA, 'monto' => 5000]);
        expect((float) $cajaA->refresh()->total_gastos)->toBe(5000.0);

        $gasto->update(['fecha' => $fechaB]);

        expect((float) $cajaA->refresh()->total_gastos)->toBe(0.0);
    });
});
