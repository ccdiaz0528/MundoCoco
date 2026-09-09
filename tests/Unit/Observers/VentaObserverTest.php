<?php

use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\Venta;

describe('VentaObserver', function () {
    beforeEach(function () {
        MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        MetodoPago::factory()->create(['nombre' => MetodoPago::TRANSFERENCIA]);
        MetodoPago::factory()->create(['nombre' => MetodoPago::TARJETA]);
    });

    it('recalcula importes exactos para la fecha de venta', function () {
        $fecha = now()->startOfDay();
        $caja = Caja::factory()->create(['fecha' => $fecha, 'estado' => 'abierta', 'saldo_inicial' => 1000, 'saldo_real' => 1000, 'diferencia' => null]);

        Venta::factory()->create([
            'metodo_pago_id' => MetodoPago::where('nombre', MetodoPago::EFECTIVO)->value('id'),
            'total' => 150,
            'fecha_venta' => $fecha,
        ]);
        Venta::factory()->create([
            'metodo_pago_id' => MetodoPago::where('nombre', MetodoPago::TRANSFERENCIA)->value('id'),
            'total' => 200,
            'fecha_venta' => $fecha,
        ]);

        $caja->refresh();

        expect((float) $caja->total_efectivo)->toBe(150.0)
            ->and((float) $caja->total_transferencias)->toBe(200.0)
            ->and((float) $caja->total_tarjetas)->toBe(0.0)
            ->and((float) $caja->total_ventas)->toBe(350.0)
            ->and((float) $caja->saldo_real)->toBe(1000.0)
            ->and($caja->diferencia)->toBeNull();
    });

    it('quita del total una venta eliminada', function () {
        $fecha = now()->startOfDay();
        $caja = Caja::factory()->create(['fecha' => $fecha, 'estado' => 'abierta']);
        $venta = Venta::factory()->create([
            'metodo_pago_id' => MetodoPago::where('nombre', MetodoPago::EFECTIVO)->value('id'),
            'total' => 125,
            'fecha_venta' => $fecha,
        ]);

        $venta->delete();
        $caja->refresh();

        expect((float) $caja->total_efectivo)->toBe(0.0)
            ->and((float) $caja->total_ventas)->toBe(0.0);
    });
});
