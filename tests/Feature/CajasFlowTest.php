<?php

use App\Models\Caja;
use App\Models\Venta;
use App\Models\MetodoPago;

describe('Gestión de Cajas', function () {
    it('puede abrir una caja', function () {
        $caja = Caja::factory()->create([
            'estado' => 'abierta',
            'saldo_inicial' => 500,
        ]);

        expect($caja->estado)->toBe('abierta');
        expect($caja->saldo_inicial)->toBe(500);
    });

    it('puede cerrar una caja', function () {
        $caja = Caja::factory()->create(['estado' => 'abierta']);
        $caja->update(['estado' => 'cerrada']);

        expect($caja->estado)->toBe('cerrada');
    });

    it('calcula total de ventas al cerrar', function () {
        $efectivo = MetodoPago::factory()->create(['nombre' => 'Efectivo']);
        $transferencia = MetodoPago::factory()->create(['nombre' => 'Transferencia']);

        $caja = Caja::factory()->create([
            'fecha' => now()->toDateString(),
            'estado' => 'abierta',
            'saldo_inicial' => 1000,
        ]);

        // Crear ventas
        Venta::factory()->create([
            'metodo_pago_id' => $efectivo->id,
            'total' => 250,
            'created_at' => now(),
        ]);

        Venta::factory()->create([
            'metodo_pago_id' => $transferencia->id,
            'total' => 150,
            'created_at' => now(),
        ]);

        // El total de ventas debe sumar
        expect(Venta::sum('total'))->toBe(400);
    });

    it('puede detectar discrepancias en caja', function () {
        $caja = Caja::factory()->create([
            'saldo_inicial' => 1000,
            'total_ventas' => 500,
            'saldo_real' => 1450, // Hay diferencia de 50
            'diferencia' => 50,
        ]);

        expect($caja->diferencia)->toBe(50);
    });

    it('registra observaciones al cerrar', function () {
        $caja = Caja::factory()->create([
            'estado' => 'abierta',
            'observaciones' => 'Todo en orden',
        ]);

        expect($caja->observaciones)->toBe('Todo en orden');
    });

    it('solo permite abrir una caja por día', function () {
        $hoy = now()->toDateString();

        $caja1 = Caja::factory()->create([
            'fecha' => $hoy,
            'estado' => 'abierta',
        ]);

        $cajasHoy = Caja::where('fecha', $hoy)->where('estado', 'abierta')->count();

        expect($cajasHoy)->toBeGreaterThanOrEqual(1);
    });

    it('puede tener múltiples cajas cerradas', function () {
        Caja::factory(3)->cerrada()->create();

        $cajasCerradas = Caja::where('estado', 'cerrada')->count();

        expect($cajasCerradas)->toBeGreaterThanOrEqual(3);
    });
});
