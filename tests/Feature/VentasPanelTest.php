<?php

use App\Filament\Resources\Ventas\Pages\CreateVenta;
use App\Filament\Resources\Ventas\Pages\EditVenta;
use App\Models\AuditLog;
use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\VentaService;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/*
 * Flujo real del panel: CreateVenta/EditVenta deben delegar en VentaService
 * para que precios, stock, trazabilidad (RF12), caja y auditoría sean los
 * mismos que prueban los tests de servicio.
 */
describe('Ventas desde el panel Filament', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        $usuario = User::factory()->create();
        $usuario->assignRole('Operador');
        $this->actingAs($usuario);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->efectivo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $this->producto = Producto::factory()->create(['precio_venta' => '5000.00', 'stock_actual' => 10, 'stock_minimo' => 2]);
    });

    it('crea la venta con precio del servidor, descuenta stock y la traza', function () {
        Livewire::test(CreateVenta::class)
            ->fillForm([
                'metodo_pago_id' => $this->efectivo->id,
                'total' => 1,
                'detalles' => [
                    ['producto_id' => $this->producto->id, 'cantidad' => 3, 'precio_unitario' => 1, 'subtotal' => 1],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotNotified('No se pudo registrar la venta');

        $venta = Venta::with('detalles')->sole();
        expect((string) $venta->total)->toBe('15000.00')
            ->and($venta->detalles)->toHaveCount(1)
            ->and((string) $venta->detalles->first()->precio_unitario)->toBe('5000.00')
            ->and($this->producto->fresh()->stock_actual)->toBe(7);

        $movimiento = MovimientoInventario::where('referencia_type', Venta::class)->sole();
        expect($movimiento->tipo)->toBe(MovimientoInventario::TIPO_VENTA)
            ->and($movimiento->cantidad)->toBe(3)
            ->and($movimiento->referencia_type)->toBe(Venta::class)
            ->and($movimiento->referencia_id)->toBe($venta->id);

        expect(AuditLog::where('accion', 'venta_creada')->count())->toBe(1);
    });

    it('rechaza sobreventa y no deja nada a medias', function () {
        Livewire::test(CreateVenta::class)
            ->fillForm([
                'metodo_pago_id' => $this->efectivo->id,
                'detalles' => [['producto_id' => $this->producto->id, 'cantidad' => 11]],
            ])
            ->call('create')
            ->assertNotified('No se pudo registrar la venta');

        expect(Venta::count())->toBe(0)
            ->and(MovimientoInventario::where('referencia_type', Venta::class)->count())->toBe(0)
            ->and($this->producto->fresh()->stock_actual)->toBe(10);
    });

    it('rechaza ventas cuando la caja del día está cerrada', function () {
        Caja::factory()->create(['fecha' => today(), 'estado' => 'cerrada']);

        Livewire::test(CreateVenta::class)
            ->fillForm([
                'metodo_pago_id' => $this->efectivo->id,
                'detalles' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
            ])
            ->call('create')
            ->assertNotified('No se pudo registrar la venta');

        expect(Venta::count())->toBe(0)
            ->and($this->producto->fresh()->stock_actual)->toBe(10);
    });

    it('al editar ajusta solo la diferencia neta de stock y traza cada cambio', function () {
        $otro = Producto::factory()->create(['precio_venta' => '2000.00', 'stock_actual' => 5]);
        $venta = app(VentaService::class)->crear(
            ['metodo_pago_id' => $this->efectivo->id],
            [['producto_id' => $this->producto->id, 'cantidad' => 4]],
        );

        Livewire::test(EditVenta::class, ['record' => $venta->getRouteKey()])
            ->fillForm([
                'detalles' => [
                    ['producto_id' => $this->producto->id, 'cantidad' => 1],
                    ['producto_id' => $otro->id, 'cantidad' => 2],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotNotified('No se pudo editar la venta');

        $venta->refresh();
        expect((string) $venta->total)->toBe('9000.00')
            ->and($venta->detalles)->toHaveCount(2)
            ->and($this->producto->fresh()->stock_actual)->toBe(9)
            ->and($otro->fresh()->stock_actual)->toBe(3);

        $devolucion = MovimientoInventario::where('tipo', MovimientoInventario::TIPO_DEVOLUCION)->sole();
        expect($devolucion->producto_id)->toBe($this->producto->id)
            ->and($devolucion->cantidad)->toBe(3)
            ->and([$devolucion->stock_anterior, $devolucion->stock_nuevo])->toBe([6, 9])
            ->and($devolucion->referencia_id)->toBe($venta->id);

        $salida = MovimientoInventario::where('producto_id', $otro->id)->where('referencia_type', Venta::class)->sole();
        expect($salida->tipo)->toBe(MovimientoInventario::TIPO_VENTA)
            ->and($salida->cantidad)->toBe(2);

        $auditoria = AuditLog::where('accion', 'venta_editada')->sole();
        expect($auditoria->valores_anteriores['total'])->toBe('20000.00');
    });

    it('al editar valida el stock contando lo ya reservado por la venta', function () {
        $venta = app(VentaService::class)->crear(
            ['metodo_pago_id' => $this->efectivo->id],
            [['producto_id' => $this->producto->id, 'cantidad' => 4]],
        );

        // Stock 6 + 4 reservadas = 10 disponibles: 10 pasa, 11 no.
        Livewire::test(EditVenta::class, ['record' => $venta->getRouteKey()])
            ->fillForm(['detalles' => [['producto_id' => $this->producto->id, 'cantidad' => 11]]])
            ->call('save')
            ->assertNotified('No se pudo editar la venta');

        expect($this->producto->fresh()->stock_actual)->toBe(6)
            ->and($venta->detalles()->sole()->cantidad)->toBe(4)
            ->and(MovimientoInventario::where('referencia_type', Venta::class)->count())->toBe(1);

        Livewire::test(EditVenta::class, ['record' => $venta->getRouteKey()])
            ->fillForm(['detalles' => [['producto_id' => $this->producto->id, 'cantidad' => 10]]])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotNotified('No se pudo editar la venta');

        expect($this->producto->fresh()->stock_actual)->toBe(0);
    });

    it('recalcula la caja abierta al editar una venta', function () {
        $venta = app(VentaService::class)->crear(
            ['metodo_pago_id' => $this->efectivo->id],
            [['producto_id' => $this->producto->id, 'cantidad' => 2]],
        );
        $caja = Caja::factory()->create(['fecha' => today(), 'estado' => 'abierta', 'saldo_inicial' => '0.00']);

        Livewire::test(EditVenta::class, ['record' => $venta->getRouteKey()])
            ->fillForm(['detalles' => [['producto_id' => $this->producto->id, 'cantidad' => 3]]])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotNotified('No se pudo editar la venta');

        expect((string) $caja->fresh()->total_ventas)->toBe('15000.00')
            ->and((string) $caja->fresh()->total_efectivo)->toBe('15000.00');
    });
});
