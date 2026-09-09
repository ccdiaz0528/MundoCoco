<?php

use App\Models\MetodoPago;
use App\Models\Venta;
use App\Services\CajaService;
use Illuminate\Validation\ValidationException;

describe('CajaService', function () {
    it('impide abrir dos cajas para la misma fecha', function () {
        $servicio = app(CajaService::class);
        $fecha = now()->toDateString();
        $servicio->abrir(['fecha' => $fecha, 'saldo_inicial' => 100]);

        expect(fn () => $servicio->abrir(['fecha' => $fecha, 'saldo_inicial' => 100]))
            ->toThrow(ValidationException::class);
    });

    it('cierra con la diferencia calculada desde ventas fechadas en la caja', function () {
        $fecha = now()->toDateString();
        $efectivo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $caja = app(CajaService::class)->abrir(['fecha' => $fecha, 'saldo_inicial' => 100]);
        Venta::factory()->create(['metodo_pago_id' => $efectivo->id, 'total' => 250, 'fecha_venta' => $fecha]);

        app(CajaService::class)->cerrar($caja, ['saldo_real' => 340]);

        expect((float) $caja->fresh()->total_ventas)->toBe(250.0)
            ->and((float) $caja->fresh()->diferencia)->toBe(-10.0)
            ->and($caja->fresh()->estado)->toBe('cerrada');
    });
});
