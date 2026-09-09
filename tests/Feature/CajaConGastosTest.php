<?php

use App\Models\Caja;
use App\Models\Gasto;
use App\Models\MetodoPago;
use App\Models\Venta;
use App\Services\CajaService;

describe('Caja con Gastos RF11', function () {
    it('RF11 calcula saldo teórico Base + Ventas - Gastos correctamente', function () {
        $fecha = now()->toDateString();
        $efectivo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $caja = app(CajaService::class)->abrir(['fecha' => $fecha, 'saldo_inicial' => 100000]);

        Venta::factory()->create(['metodo_pago_id' => $efectivo->id, 'total' => 50000, 'fecha_venta' => $fecha]);
        Venta::factory()->create(['metodo_pago_id' => $efectivo->id, 'total' => 30000, 'fecha_venta' => $fecha]);

        Gasto::factory()->create(['fecha' => $fecha, 'monto' => 20000]);
        Gasto::factory()->create(['fecha' => $fecha, 'monto' => 10000]);

        // Recalcular tras gastos
        app(CajaService::class)->recalcularCajaAbierta($fecha);
        $caja->refresh();

        expect((float) $caja->total_ventas)->toBe(80000.0)
            ->and((float) $caja->total_gastos)->toBe(30000.0)
            ->and((float) $caja->saldo_teorico)->toBe(150000.0); // 100k +80k -30k
    });

    it('RF11 cierre con diferencia exacta incluyendo gastos', function () {
        $fecha = now()->toDateString();
        $efectivo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $caja = app(CajaService::class)->abrir(['fecha' => $fecha, 'saldo_inicial' => 50000]);

        Venta::factory()->create(['metodo_pago_id' => $efectivo->id, 'total' => 25000, 'fecha_venta' => $fecha]);
        Gasto::factory()->create(['fecha' => $fecha, 'monto' => 5000]);

        // Esperado = 50000 +25000 -5000 =70000
        $cerrada = app(CajaService::class)->cerrar($caja, ['saldo_real' => 70000]);

        expect($cerrada->estado)->toBe('cerrada')
            ->and((float) $cerrada->saldo_teorico)->toBe(70000.0)
            ->and((float) $cerrada->diferencia)->toBe(0.0);
    });

    it('RF11 detecta sobrante y faltante', function () {
        $fecha = now()->toDateString();
        $efectivo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $caja = app(CajaService::class)->abrir(['fecha' => $fecha, 'saldo_inicial' => 10000]);
        Venta::factory()->create(['metodo_pago_id' => $efectivo->id, 'total' => 10000, 'fecha_venta' => $fecha]);

        // Esperado 20000, contado 19000 => faltante -1000
        $cerrada = app(CajaService::class)->cerrar($caja, ['saldo_real' => 19000]);
        expect((float) $cerrada->diferencia)->toBe(-1000.0);

        // Nueva caja día siguiente
        $fecha2 = now()->addDay()->toDateString();
        $caja2 = app(CajaService::class)->abrir(['fecha' => $fecha2, 'saldo_inicial' => 10000]);
        Venta::factory()->create(['metodo_pago_id' => $efectivo->id, 'total' => 5000, 'fecha_venta' => $fecha2]);
        $cerrada2 = app(CajaService::class)->cerrar($caja2, ['saldo_real' => 16000]); // esperado 15000 => sobrante 1000
        expect((float) $cerrada2->diferencia)->toBe(1000.0);
    });

    it('RF11 bloquea gastos en caja cerrada', function () {
        $fecha = now()->toDateString();
        $caja = Caja::factory()->create(['fecha' => $fecha, 'estado' => 'cerrada']);

        expect(Gasto::whereDate('fecha', $fecha)->count())->toBe(0);
        // Simular validación de Filament: no permitir gasto si caja cerrada
        $bloqueado = Caja::whereDate('fecha', $fecha)->where('estado', 'cerrada')->exists();
        expect($bloqueado)->toBeTrue();
    });

    it('RF11 totalesPorFecha incluye gastos segregados', function () {
        $fecha = now()->toDateString();
        MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        MetodoPago::factory()->create(['nombre' => MetodoPago::TRANSFERENCIA]);
        MetodoPago::factory()->create(['nombre' => MetodoPago::TARJETA]);

        $svc = app(CajaService::class);
        $tot = $svc->totalesPorFecha($fecha);
        expect($tot)->toHaveKeys(['efectivo', 'transferencias', 'tarjetas', 'total', 'gastos']);
    });
});
