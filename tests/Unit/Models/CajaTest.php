<?php

use App\Models\Caja;

describe('Caja', function () {
    it('puede crear una caja', function () {
        $caja = Caja::factory()->create([
            'saldo_inicial' => 500,
            'estado' => 'abierta',
        ]);

        expect($caja->saldo_inicial)->toBe(500);
        expect($caja->estado)->toBe('abierta');
    });

    it('caja abierta inicia con saldo inicial como saldo real', function () {
        $caja = Caja::factory()->create([
            'saldo_inicial' => 1000,
            'total_ventas' => 0,
            'saldo_real' => 1000,
        ]);

        expect($caja->saldo_real)->toBe(1000);
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

        expect($caja->total_efectivo)->toBe(300);
        expect($caja->total_transferencias)->toBe(200);
        expect($caja->total_tarjetas)->toBe(150);
    });

    it('calcula diferencia entre saldo real y esperado', function () {
        $caja = Caja::factory()->create([
            'saldo_inicial' => 1000,
            'total_ventas' => 500,
            'saldo_real' => 1500,
            'diferencia' => 0,
        ]);

        $diferencia = $caja->saldo_real - ($caja->saldo_inicial + $caja->total_ventas);

        expect($diferencia)->toBe(0);
    });

    it('puede tener observaciones', function () {
        $caja = Caja::factory()->create([
            'observaciones' => 'Caja de mañana sin discrepancias',
        ]);

        expect($caja->observaciones)->toBe('Caja de mañana sin discrepancias');
    });
});
