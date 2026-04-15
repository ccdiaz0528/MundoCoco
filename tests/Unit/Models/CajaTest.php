<?php

use App\Models\Caja;

describe('Caja', function () {
    it('puede crear una caja', function () {
        $caja = Caja::factory()->create([
            'saldo_inicial' => 500,
            'estado' => 'abierta',
        ]);

        expect((float)$caja->saldo_inicial)->toEqual(500.0);
        expect($caja->estado)->toBe('abierta');
    });

    it('caja abierta inicia con saldo inicial como saldo real', function () {
        $caja = Caja::factory()->create([
            'saldo_inicial' => 1000,
            'total_ventas' => 0,
            'saldo_real' => 1000,
        ]);

        expect((float)$caja->saldo_real)->toEqual(1000.0);
    });

    it('puede cerrarse', function () {
        $caja = Caja::factory()->create(['estado' => 'abierta']);
        $caja->update(['estado' => 'cerrada']);

        expect($caja->estado)->toBe('cerrada');
    });

    it('registra totales por método de pago', function () {
        $caja = Caja::factory()->create([
            'total_efectivo' => 300,
            'total_transferencias' => 200,
            'total_tarjetas' => 150,
        ]);

        expect((float)$caja->total_efectivo)->toEqual(300.0);
        expect((float)$caja->total_transferencias)->toEqual(200.0);
        expect((float)$caja->total_tarjetas)->toEqual(150.0);
    });

    it('calcula diferencia entre saldo real y esperado', function () {
        $caja = Caja::factory()->create([
            'saldo_inicial' => 1000,
            'total_ventas' => 500,
            'saldo_real' => 1500,
            'diferencia' => 0,
        ]);

        $diferencia = (float)$caja->saldo_real - ((float)$caja->saldo_inicial + (float)$caja->total_ventas);

        expect($diferencia)->toEqual(0.0);
    });

    it('puede tener observaciones', function () {
        $caja = Caja::factory()->create([
            'observaciones' => 'Caja de mañana sin discrepancias',
        ]);

        expect($caja->observaciones)->toBe('Caja de mañana sin discrepancias');
    });
});
