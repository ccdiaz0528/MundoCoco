<?php

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\User;
use App\Services\CajaService;
use App\Services\VentaService;
use App\Support\AuditoriaLegible;
use Database\Seeders\MetodoPagoSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/*
 * RNF04: la auditoría se lee por personas, no por desarrolladores. Estas
 * pruebas fijan que el panel muestre frases claras en lugar del JSON técnico
 * y que el detalle completo (aunque sea largo) esté disponible en el modal.
 */

it('muestra el cambio de precio de un producto en lenguaje claro', function () {
    $producto = Producto::factory()->create([
        'nombre' => 'Aceite de coco 250 ml',
        'precio_venta' => '10000.00',
    ]);

    $producto->update(['precio_venta' => '12000.00']);

    $log = AuditLog::where('accion', 'producto_actualizado')->sole();

    expect(AuditoriaLegible::accion($log->accion))->toBe('Producto modificado')
        ->and(AuditoriaLegible::registro($log))->toBe('Aceite de coco 250 ml')
        ->and(AuditoriaLegible::resumen($log))->toBe('Aceite de coco 250 ml · Precio de venta: $ 10.000 → $ 12.000')
        ->and(AuditoriaLegible::detalles($log))->toBe([
            ['campo' => 'Precio de venta', 'antes' => '$ 10.000', 'despues' => '$ 12.000'],
        ]);
});

it('explica un ajuste de stock como el paso de una cantidad a otra', function () {
    $producto = Producto::factory()->create(['nombre' => 'Cocada', 'stock_actual' => 5]);

    $producto->update(['stock_actual' => 3]);

    $log = AuditLog::where('accion', 'stock_ajuste')->sole();

    expect(AuditoriaLegible::resumen($log))->toBe('Cocada · Stock 5 → 3 · Ajuste negativo')
        ->and(AuditoriaLegible::detalles($log))->toBe([
            ['campo' => 'Stock', 'antes' => '5', 'despues' => '3'],
            ['campo' => 'Tipo de movimiento', 'antes' => '—', 'despues' => 'Ajuste negativo'],
        ]);
});

it('resume una venta nueva con su total y método de pago', function () {
    $this->seed(MetodoPagoSeeder::class);

    $efectivo = MetodoPago::where('nombre', MetodoPago::EFECTIVO)->value('id');
    $producto = Producto::factory()->create(['precio_venta' => '3000.00', 'stock_actual' => 10]);

    $venta = app(VentaService::class)->crear(
        ['metodo_pago_id' => $efectivo],
        [['producto_id' => $producto->id, 'cantidad' => 3]],
    );

    $log = AuditLog::where('accion', 'venta_creada')->sole();

    expect(AuditoriaLegible::registro($log))->toBe('Venta #'.$venta->id)
        ->and(AuditoriaLegible::resumen($log))->toBe('Venta #'.$venta->id.' · $ 9.000 · Efectivo')
        ->and(collect(AuditoriaLegible::detalles($log))->pluck('campo')->all())
        ->toContain('Total', 'Método de pago', 'Fecha de la venta');
});

it('nombra el producto cuando se ajustó el precio dentro de una venta', function () {
    $this->seed(MetodoPagoSeeder::class);

    $efectivo = MetodoPago::where('nombre', MetodoPago::EFECTIVO)->value('id');
    $producto = Producto::factory()->create([
        'nombre' => 'Cocada',
        'precio_venta' => '10000.00',
        'stock_actual' => 5,
    ]);

    app(VentaService::class)->crear(
        ['metodo_pago_id' => $efectivo],
        [['producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => '9000']],
    );

    $log = AuditLog::where('accion', 'venta_precio_modificado')->sole();

    expect(AuditoriaLegible::resumen($log))->toContain('1 producto(s) con precio ajustado')
        ->and(AuditoriaLegible::detalles($log))->toBe([[
            'campo' => 'Cocada: precio de lista vs. precio aplicado',
            'antes' => '$ 10.000',
            'despues' => '$ 9.000',
        ]]);
});

it('en una venta modificada lista solo lo que cambió, con antes y después', function () {
    $this->seed(MetodoPagoSeeder::class);

    $efectivo = MetodoPago::where('nombre', MetodoPago::EFECTIVO)->value('id');
    $producto = Producto::factory()->create(['precio_venta' => '4000.00', 'stock_actual' => 10]);

    $venta = app(VentaService::class)->crear(
        ['metodo_pago_id' => $efectivo, 'observaciones' => 'sin novedad'],
        [['producto_id' => $producto->id, 'cantidad' => 1]],
    );

    app(VentaService::class)->actualizar(
        $venta,
        ['metodo_pago_id' => $efectivo, 'observaciones' => 'cliente pidió factura'],
        [['producto_id' => $producto->id, 'cantidad' => 2]],
    );

    $log = AuditLog::where('accion', 'venta_editada')->sole();
    $detalles = collect(AuditoriaLegible::detalles($log))->keyBy('campo');

    // Ni el método de pago ni la fecha se tocaron: no deben aparecer.
    expect($detalles->keys()->all())->toBe(['Total', 'Observaciones'])
        ->and($detalles['Total']['antes'])->toBe('$ 4.000')
        ->and($detalles['Total']['despues'])->toBe('$ 8.000')
        ->and($detalles['Observaciones']['antes'])->toBe('sin novedad')
        ->and($detalles['Observaciones']['despues'])->toBe('cliente pidió factura')
        ->and(AuditoriaLegible::resumen($log))->toBe('Venta #'.$venta->id.' · Cambió: Total, Observaciones');
});

it('muestra el cierre de caja completo aunque tenga muchos datos', function () {
    $caja = app(CajaService::class)->abrir(['fecha' => today(), 'saldo_inicial' => 50000]);
    app(CajaService::class)->cerrar($caja, ['saldo_real' => 48000]);

    $log = AuditLog::where('accion', 'caja_cerrada')->sole();
    $campos = collect(AuditoriaLegible::detalles($log))->pluck('campo');

    expect(AuditoriaLegible::resumen($log))->toContain('Saldo esperado $ 50.000')
        ->and(AuditoriaLegible::resumen($log))->toContain('Diferencia $ -2.000')
        ->and($campos->all())->toContain('Ventas en efectivo', 'Saldo contado', 'Cerrada el')
        ->and($campos)->not->toContain('Id')
        ->and($campos)->not->toContain('Created At');
});

it('abre el detalle de auditoría en un modal de solo lectura', function () {
    $this->seed(RoleSeeder::class);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(tap(User::factory()->create())->assignRole('Admin'));

    $producto = Producto::factory()->create(['nombre' => 'Cocada', 'precio_venta' => '10000.00']);
    $producto->update(['precio_venta' => '12345.00']);

    $log = AuditLog::where('accion', 'producto_actualizado')->sole();

    Livewire::test(ListAuditLogs::class)
        ->assertSee('Producto modificado')
        ->assertSee('Cocada')
        ->assertSee('Precio de venta: $ 10.000 → $ 12.345')
        ->assertTableActionExists('verDetalle', record: $log)
        ->mountTableAction('verDetalle', $log)
        ->assertActionMounted();

    // El contenido del modal se lee completo, sin el JSON técnico ni cortes.
    $this->view('filament.audit-detalle', ['log' => $log])
        ->assertSee('Campo')
        ->assertSee('Antes')
        ->assertSee('Después')
        ->assertSee('Precio de venta')
        ->assertDontSee('updated_at');
});
