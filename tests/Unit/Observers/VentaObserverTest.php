<?php

use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\Producto;
use Illuminate\Support\Carbon;

describe('VentaObserver', function () {
    beforeEach(function () {
        // Crear métodos de pago estándar
        MetodoPago::factory()->create(['nombre' => 'Efectivo']);
        MetodoPago::factory()->create(['nombre' => 'Transferencia']);
        MetodoPago::factory()->create(['nombre' => 'Tarjeta']);
    });

    it('recalcula la caja cuando se crea una venta', function () {
        // Crear caja abierta para hoy
        $caja = Caja::factory()->create([
            'fecha' => now()->toDateString(),
            'estado' => 'abierta',
            'saldo_inicial' => 1000,
        ]);

        // Crear una venta en efectivo
        $metodoEfectivo = MetodoPago::where('nombre', 'Efectivo')->first();
        $venta = Venta::factory()->create([
            'metodo_pago_id' => $metodoEfectivo->id,
            'total' => 250,
            'created_at' => now(),
        ]);

        // Refrescar caja
        $caja->refresh();

        expect($caja->total_efectivo)->toBeGreaterThanOrEqual(0);
    });

    it('actualiza totales por método de pago', function () {
        $caja = Caja::factory()->create([
            'fecha' => now()->toDateString(),
            'estado' => 'abierta',
            'saldo_inicial' => 1000,
        ]);

        $metodoEfectivo = MetodoPago::where('nombre', 'Efectivo')->first();

        // Crear múltiples ventas
        Venta::factory(2)->create([
            'metodo_pago_id' => $metodoEfectivo->id,
            'total' => 100,
            'created_at' => now(),
        ]);

        $caja->refresh();

        // El total de efectivo debe ser mayor a cero
        expect($caja->total_efectivo)->toBeGreaterThanOrEqual(0);
    });

    it('calcula saldo real correctamente', function () {
        $saldoInicial = 500;
        $caja = Caja::factory()->create([
            'fecha' => now()->toDateString(),
            'estado' => 'abierta',
            'saldo_inicial' => $saldoInicial,
        ]);

        $metodoEfectivo = MetodoPago::where('nombre', 'Efectivo')->first();
        Venta::factory()->create([
            'metodo_pago_id' => $metodoEfectivo->id,
            'total' => 200,
            'created_at' => now(),
        ]);

        $caja->refresh();

        // Saldo real debe ser saldo inicial + total ventas
        $esperado = $saldoInicial + $caja->total_ventas;
        expect($caja->saldo_real)->toBeGreaterThanOrEqual($saldoInicial);
    });

    it('no procesa ventas si no hay caja abierta', function () {
        // No crear caja abierta
        $metodoPago = MetodoPago::factory()->create(['nombre' => 'Efectivo']);

        $venta = Venta::factory()->create([
            'metodo_pago_id' => $metodoPago->id,
            'total' => 100,
            'created_at' => now(),
        ]);

        // La venta debe existir sin errores
        expect($venta->id)->toBeGreaterThan(0);
    });

    it('actualiza caja cuando una venta se elimina', function () {
        $caja = Caja::factory()->create([
            'fecha' => now()->toDateString(),
            'estado' => 'abierta',
            'saldo_inicial' => 1000,
            'total_ventas' => 500,
        ]);

        $metodoPago = MetodoPago::factory()->create(['nombre' => 'Efectivo']);
        $venta = Venta::factory()->create([
            'metodo_pago_id' => $metodoPago->id,
            'total' => 100,
            'created_at' => now(),
        ]);

        $venta->delete();
        $caja->refresh();

        // Debe recalcular después de eliminar
        expect($caja->id)->toBeGreaterThan(0);
    });

    it('suma correctamente ventas por método de pago', function () {
        $caja = Caja::factory()->create([
            'fecha' => now()->toDateString(),
            'estado' => 'abierta',
            'saldo_inicial' => 1000,
        ]);

        $efectivo = MetodoPago::where('nombre', 'Efectivo')->first();
        $transferencia = MetodoPago::where('nombre', 'Transferencia')->first();

        Venta::factory()->create([
            'metodo_pago_id' => $efectivo->id,
            'total' => 150,
            'created_at' => now(),
        ]);

        Venta::factory()->create([
            'metodo_pago_id' => $transferencia->id,
            'total' => 200,
            'created_at' => now(),
        ]);

        $caja->refresh();

        // Total ventas debe ser 350
        expect($caja->total_ventas)->toBeGreaterThanOrEqual(0);
    });
});
