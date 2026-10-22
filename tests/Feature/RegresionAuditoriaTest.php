<?php

use App\Filament\Resources\Cajas\Pages\EditCaja;
use App\Models\AuditLog;
use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Services\CajaService;
use App\Services\InventarioService;
use App\Services\VentaService;
use Database\Seeders\MetodoPagoSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;

/*
 * Regresiones de la auditoría técnica. Cada prueba fija un fallo real:
 * caja duplicada, totales pisados al editar, stock perdido con productos
 * eliminados, fórmulas en exportaciones y auditoría de precios.
 */

it('no permite abrir una caja en una fecha que ya fue cerrada', function () {
    $svc = app(CajaService::class);
    $caja = $svc->abrir(['fecha' => today(), 'saldo_inicial' => 1000]);
    $svc->cerrar($caja, ['saldo_real' => 1000]);

    expect(fn () => $svc->abrir(['fecha' => today(), 'saldo_inicial' => 0]))
        ->toThrow(ValidationException::class);

    expect(Caja::whereDate('fecha', today())->count())->toBe(1);
});

it('editar una caja no pisa los totales ni el teórico calculado', function () {
    $this->seed([RoleSeeder::class, MetodoPagoSeeder::class]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $admin = tap(User::factory()->create())->assignRole('Admin');
    $this->actingAs($admin);

    $efectivo = MetodoPago::where('nombre', MetodoPago::EFECTIVO)->value('id');
    $producto = Producto::factory()->create(['stock_actual' => 10, 'precio_venta' => '1000.00']);

    $caja = app(CajaService::class)->abrir(['fecha' => today(), 'saldo_inicial' => 0]);

    // El formulario se abre con los totales en cero; luego entra una venta.
    $formulario = Livewire::test(EditCaja::class, ['record' => $caja->id]);

    app(VentaService::class)->crear(
        ['metodo_pago_id' => $efectivo],
        [['producto_id' => $producto->id, 'cantidad' => 3]],
    );

    expect((float) $caja->fresh()->total_ventas)->toBe(3000.0);

    $formulario->fillForm(['observaciones' => 'solo cambio observaciones'])->call('save');

    $caja->refresh();
    expect((float) $caja->total_ventas)->toBe(3000.0)
        ->and((float) $caja->saldo_teorico)->toBe(3000.0);

    // Cambiar el saldo inicial recalcula el teórico (RF11: Base + Ventas - ...).
    $formulario->fillForm(['saldo_inicial' => 1000])->call('save');

    expect((float) $caja->fresh()->saldo_teorico)->toBe(4000.0);
});

it('anular una venta de un producto eliminado devuelve el stock', function () {
    $this->seed(MetodoPagoSeeder::class);

    $efectivo = MetodoPago::where('nombre', MetodoPago::EFECTIVO)->value('id');
    $producto = Producto::factory()->create(['stock_actual' => 5]);

    $venta = app(VentaService::class)->crear(
        ['metodo_pago_id' => $efectivo],
        [['producto_id' => $producto->id, 'cantidad' => 2]],
    );

    $producto->delete();
    app(VentaService::class)->anular($venta, 'devolución de prueba');

    $producto = Producto::withTrashed()->findOrFail($producto->id);

    expect((int) $producto->stock_actual)->toBe(5);

    $movimiento = MovimientoInventario::where('referencia_id', $venta->id)
        ->where('tipo', MovimientoInventario::TIPO_DEVOLUCION)
        ->sole();

    expect($movimiento->stock_nuevo)->toBe(5);
});

it('una compra fechada después de un conteo retroactivo sigue contando', function () {
    $producto = Producto::factory()->create(['stock_actual' => 0]);
    $svc = app(InventarioService::class);

    $svc->adicionarStock($producto, 5, MovimientoInventario::TIPO_COMPRA, fecha: now()->subDays(10));
    $svc->adicionarStock($producto->fresh(), 1, MovimientoInventario::TIPO_COMPRA, fecha: now()->subDays(4));

    // Conteo físico con fecha -5 registrado después de esas compras.
    $svc->registrarInicial($producto->fresh(), 5, fecha: now()->subDays(5));

    // El stock actual es 5 (contado al día -5) + 1 (compra del día -4).
    expect($svc->calcularStockTotal($producto->fresh()))->toBe(6);
});

it('audita los cambios de precio del producto', function () {
    $producto = Producto::factory()->create(['precio_venta' => '10000.00']);

    $producto->update(['precio_venta' => '12000.00']);

    $log = AuditLog::where('accion', 'producto_actualizado')->where('modelo_id', $producto->id)->sole();

    expect($log->cambios['precio_venta'])->toBe('12000.00')
        ->and($log->valores_anteriores['precio_venta'])->toBe('10000.00');
});

it('no exporta fórmulas en CSV ni XLSX', function () {
    $this->seed([RoleSeeder::class, MetodoPagoSeeder::class]);

    $this->actingAs(tap(User::factory()->create())->assignRole('Admin'));

    Producto::factory()->create([
        'nombre' => '=1+1',
        'stock_actual' => 0,
        'stock_minimo' => 5,
    ]);

    $csv = $this->get('/reportes/export/stock_bajo/csv')->streamedContent();
    expect($csv)->toContain("'=1+1");

    $xlsx = $this->get('/reportes/export/stock_bajo/xlsx')->streamedContent();
    $ruta = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($ruta, $xlsx);

    try {
        $hoja = IOFactory::load($ruta)->getActiveSheet();
        $valores = [];

        foreach ($hoja->getRowIterator(2) as $fila) {
            foreach ($fila->getCellIterator() as $celda) {
                expect($celda->getDataType())->not->toBe('f');
                $valores[] = $celda->getValue();
            }
        }

        expect($valores)->toContain("'=1+1");
    } finally {
        @unlink($ruta);
    }
});
