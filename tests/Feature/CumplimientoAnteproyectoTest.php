<?php

use App\Filament\Pages\ConsentimientoDatos;
use App\Filament\Pages\Reportes;
use App\Filament\Resources\Movimientos\Pages\CreateMovimiento;
use App\Filament\Widgets\StockCritico;
use App\Models\AuditLog;
use App\Models\Caja;
use App\Models\Gasto;
use App\Models\MetodoPago;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Services\CajaService;
use App\Services\InventarioService;
use App\Services\ReporteService;
use App\Services\VentaService;
use Database\Seeders\MetodoPagoSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
 * Brechas cerradas frente a "DOCS DE PROYECTO/RF y RNF.docx" y el
 * anteproyecto. Cada bloque cita el requisito que verifica.
 */

describe('RF04/RF11 métodos de pago con Nequi', function () {
    it('siembra Efectivo, Nequi, Transferencia y Tarjeta sin duplicar', function () {
        $this->seed(MetodoPagoSeeder::class);
        $this->seed(MetodoPagoSeeder::class);

        expect(MetodoPago::orderBy('id')->pluck('nombre')->all())
            ->toBe(['Efectivo', 'Nequi', 'Transferencia', 'Tarjeta']);
    });

    it('la caja separa Nequi y lo suma al saldo: Base + Efectivo + Nequi - Gastos', function () {
        $this->seed(MetodoPagoSeeder::class);
        $producto = Producto::factory()->create(['precio_venta' => '10000.00', 'stock_actual' => 10]);
        $ventas = app(VentaService::class);

        $ventas->crear(['metodo_pago_id' => MetodoPago::where('nombre', MetodoPago::EFECTIVO)->value('id')], [['producto_id' => $producto->id, 'cantidad' => 1]]);
        $ventas->crear(['metodo_pago_id' => MetodoPago::where('nombre', MetodoPago::NEQUI)->value('id')], [['producto_id' => $producto->id, 'cantidad' => 2]]);

        $caja = app(CajaService::class)->abrir(['fecha' => today(), 'saldo_inicial' => '50000']);

        expect((string) $caja->total_efectivo)->toBe('10000.00')
            ->and((string) $caja->total_nequi)->toBe('20000.00')
            ->and((string) $caja->total_transferencias)->toBe('0.00')
            ->and((string) $caja->saldo_teorico)->toBe('80000.00');

        $cerrada = app(CajaService::class)->cerrar($caja, ['saldo_real' => '80000']);
        expect((string) $cerrada->diferencia)->toBe('0.00');
    });
});

describe('RF04 precio distinto del base y fecha/hora de venta', function () {
    beforeEach(function () {
        $this->metodo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $this->producto = Producto::factory()->create(['precio_venta' => '10000.00', 'stock_actual' => 10]);
    });

    it('aplica un precio distinto dentro del rango, guarda el base y lo audita', function () {
        $venta = app(VentaService::class)->crear(['metodo_pago_id' => $this->metodo->id], [
            ['producto_id' => $this->producto->id, 'cantidad' => 2, 'precio_unitario' => '8000'],
        ]);

        $detalle = $venta->detalles->first();
        expect((string) $detalle->precio_unitario)->toBe('8000.00')
            ->and((string) $detalle->precio_base)->toBe('10000.00')
            ->and((string) $venta->total)->toBe('16000.00');

        $auditoria = AuditLog::where('accion', 'venta_precio_modificado')->sole();
        expect($auditoria->cambios['lineas'][0])->toMatchArray(['precio_base' => '10000.00', 'precio_unitario' => '8000.00']);
    });

    it('rechaza un precio fuera del rango configurado', function () {
        config()->set('mundococo.precio_venta_max', 95000);

        expect(fn () => app(VentaService::class)->crear(['metodo_pago_id' => $this->metodo->id], [
            ['producto_id' => $this->producto->id, 'cantidad' => 1, 'precio_unitario' => '95001'],
        ]))->toThrow(ValidationException::class);

        expect(Venta::count())->toBe(0);
    });

    it('registra la fecha y hora indicada y la asigna a la caja de ese día', function () {
        $ayer = now()->subDay()->setTime(15, 30);
        $caja = Caja::factory()->create(['fecha' => $ayer->toDateString(), 'estado' => 'abierta', 'saldo_inicial' => '0.00']);

        $venta = app(VentaService::class)->crear(['metodo_pago_id' => $this->metodo->id, 'fecha_venta' => $ayer], [
            ['producto_id' => $this->producto->id, 'cantidad' => 1],
        ]);

        expect($venta->fecha_venta->format('Y-m-d H:i'))->toBe($ayer->format('Y-m-d H:i'))
            ->and((string) $caja->fresh()->total_ventas)->toBe('10000.00');
    });

    it('rechaza una fecha de venta futura', function () {
        expect(fn () => app(VentaService::class)->crear(['metodo_pago_id' => $this->metodo->id, 'fecha_venta' => now()->addHour()], [
            ['producto_id' => $this->producto->id, 'cantidad' => 1],
        ]))->toThrow(ValidationException::class);
    });
});

describe('RF10 roles estrictos: Administrador, Operario, Consultor', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        $this->usuario = fn (string $rol) => tap(User::factory()->create())->assignRole($rol);
    });

    it('crea exactamente los tres roles del documento', function () {
        expect(Role::orderBy('name')->pluck('name')->all())->toBe(['Admin', 'Consultor', 'Operario']);
    });

    it('Operario: registra ventas y consulta inventario, nada más', function () {
        $operario = ($this->usuario)('Operario');
        $producto = Producto::factory()->create();

        expect($operario->can('create', Venta::class))->toBeTrue()
            ->and($operario->can('viewAny', Producto::class))->toBeTrue()
            ->and($operario->can('viewAny', MovimientoInventario::class))->toBeTrue()
            ->and($operario->can('update', $producto))->toBeFalse()
            ->and($operario->can('create', MovimientoInventario::class))->toBeFalse()
            ->and($operario->can('viewAny', Caja::class))->toBeFalse()
            ->and($operario->can('viewAny', Gasto::class))->toBeFalse()
            ->and($operario->can('ver reportes'))->toBeFalse()
            ->and($operario->can('viewAny', User::class))->toBeFalse();
    });

    it('Consultor: solo visualización de reportes', function () {
        $consultor = ($this->usuario)('Consultor');

        expect($consultor->can('ver reportes'))->toBeTrue()
            ->and($consultor->can('exportar reportes'))->toBeTrue()
            ->and($consultor->can('viewAny', Venta::class))->toBeFalse()
            ->and($consultor->can('viewAny', Producto::class))->toBeFalse()
            ->and($consultor->can('viewAny', Caja::class))->toBeFalse()
            ->and($consultor->can('viewAny', MovimientoInventario::class))->toBeFalse();
    });

    it('Administrador: acceso total, incluido crear usuarios y asignar roles', function () {
        $admin = ($this->usuario)('Admin');

        expect($admin->can('create', User::class))->toBeTrue()
            ->and($admin->can('viewAny', Caja::class))->toBeTrue()
            ->and($admin->can('create', MovimientoInventario::class))->toBeTrue()
            ->and($admin->can('ver auditoria'))->toBeTrue();
    });

    it('renombra el rol Operador existente conservando a sus usuarios', function () {
        Role::where('name', 'Operario')->update(['name' => 'Operador']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $antiguo = ($this->usuario)('Operador');

        $this->seed(RoleSeeder::class);

        expect($antiguo->fresh()->hasRole('Operario'))->toBeTrue()
            ->and(Role::where('name', 'Operador')->exists())->toBeFalse();
    });

    it('cada usuario puede cambiar su contraseña desde su perfil', function () {
        $this->actingAs(($this->usuario)('Operario'))
            ->get('/admin/profile')
            ->assertOk();
    });
});

describe('Anulación de ventas (devoluciones del anteproyecto)', function () {
    beforeEach(function () {
        $this->metodo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $this->producto = Producto::factory()->create(['precio_venta' => '10000.00', 'stock_actual' => 10]);
        $this->venta = app(VentaService::class)->crear(['metodo_pago_id' => $this->metodo->id], [
            ['producto_id' => $this->producto->id, 'cantidad' => 3],
        ]);
    });

    it('devuelve el stock, la saca de caja y reportes y la audita', function () {
        $caja = app(CajaService::class)->abrir(['fecha' => today(), 'saldo_inicial' => '0']);
        expect((string) $caja->total_ventas)->toBe('30000.00');

        app(VentaService::class)->anular($this->venta, 'Cliente devolvió el pedido');

        expect($this->venta->fresh()->anulada())->toBeTrue()
            ->and($this->producto->fresh()->stock_actual)->toBe(10)
            ->and((string) $caja->fresh()->total_ventas)->toBe('0.00')
            ->and(app(ReporteService::class)->productosMasVendidos())->toHaveCount(0);

        $devolucion = MovimientoInventario::where('tipo', MovimientoInventario::TIPO_DEVOLUCION)->sole();
        expect($devolucion->cantidad)->toBe(3)
            ->and($devolucion->referencia_id)->toBe($this->venta->id);
        expect(AuditLog::where('accion', 'venta_anulada')->count())->toBe(1);
    });

    it('no permite anular sin motivo ni con la caja cerrada', function () {
        expect(fn () => app(VentaService::class)->anular($this->venta, '  '))->toThrow(ValidationException::class);

        Caja::factory()->create(['fecha' => today(), 'estado' => 'cerrada']);
        expect(fn () => app(VentaService::class)->anular($this->venta, 'Error'))->toThrow(ValidationException::class);
        expect($this->producto->fresh()->stock_actual)->toBe(7);
    });

    it('una venta anulada no se puede editar ni volver a anular', function () {
        app(VentaService::class)->anular($this->venta, 'Error de digitación');

        expect(fn () => app(VentaService::class)->actualizar($this->venta, [], [['producto_id' => $this->producto->id, 'cantidad' => 1]]))
            ->toThrow(ValidationException::class)
            ->and(fn () => app(VentaService::class)->anular($this->venta, 'Otra vez'))
            ->toThrow(ValidationException::class);
    });
});

describe('RF01 eliminar productos (borrado lógico)', function () {
    it('el producto eliminado sale del catálogo, su historial sigue íntegro y es restaurable', function () {
        $metodo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $producto = Producto::factory()->create(['precio_venta' => '10000.00', 'stock_actual' => 5]);
        $venta = app(VentaService::class)->crear(['metodo_pago_id' => $metodo->id], [['producto_id' => $producto->id, 'cantidad' => 1]]);

        $producto->delete();

        expect(Producto::find($producto->id))->toBeNull()
            ->and($venta->detalles()->first()->producto->nombre)->toBe($producto->nombre)
            ->and(fn () => app(VentaService::class)->crear(['metodo_pago_id' => $metodo->id], [['producto_id' => $producto->id, 'cantidad' => 1]]))
            ->toThrow(ValidationException::class);

        $producto->restore();
        expect(Producto::find($producto->id))->not->toBeNull();
    });
});

describe('RF02/RF03/RF05 inventario con fecha, varios productos y stock calculado', function () {
    it('RF05 cuenta solo desde el último inventario inicial', function () {
        $producto = Producto::factory()->create(['stock_actual' => 0]);
        $svc = app(InventarioService::class);

        $svc->registrarInicial($producto, 20);
        $svc->adicionarStock($producto, 10, MovimientoInventario::TIPO_COMPRA);
        // Conteo físico: el inicial fija la base; lo anterior ya no suma.
        $svc->registrarInicial($producto, 8);
        $svc->retirarStock($producto, 3, MovimientoInventario::TIPO_MERMA);

        expect($svc->calcularStockTotal($producto))->toBe(5)
            ->and($producto->fresh()->stock_actual)->toBe(5)
            ->and($svc->inconsistenciasStock())->toHaveCount(0);
    });

    it('RF05 detecta un stock registrado que no cuadra con los movimientos (precisión OE4)', function () {
        $producto = Producto::factory()->create(['stock_actual' => 10]);
        Producto::query()->whereKey($producto->id)->update(['stock_actual' => 7]); // cambio sin movimiento

        $inconsistencia = app(InventarioService::class)->inconsistenciasStock()->sole();
        expect($inconsistencia['registrado'])->toBe(7)
            ->and($inconsistencia['calculado'])->toBe(10);
    });

    it('RF03 registra varios productos con la fecha indicada en una sola transacción y audita', function () {
        $a = Producto::factory()->create(['stock_actual' => 5]);
        $b = Producto::factory()->create(['stock_actual' => 1]);
        $ayer = now()->subDay()->setTime(9, 0);

        $movimientos = app(InventarioService::class)->registrarLote(MovimientoInventario::TIPO_COMPRA, [
            ['producto_id' => $a->id, 'cantidad' => 3],
            ['producto_id' => $b->id, 'cantidad' => 4],
        ], 'Proveedor X', null, $ayer);

        expect($movimientos)->toHaveCount(2)
            ->and($a->fresh()->stock_actual)->toBe(8)
            ->and($b->fresh()->stock_actual)->toBe(5)
            ->and($movimientos->first()->fecha_movimiento->format('Y-m-d H:i'))->toBe($ayer->format('Y-m-d H:i'))
            ->and(AuditLog::where('accion', 'stock_ajuste')->count())->toBe(2);
    });

    it('un lote con una línea inválida no deja nada a medias', function () {
        $a = Producto::factory()->create(['stock_actual' => 5]);
        $b = Producto::factory()->create(['stock_actual' => 1]);

        expect(fn () => app(InventarioService::class)->registrarLote(MovimientoInventario::TIPO_MERMA, [
            ['producto_id' => $a->id, 'cantidad' => 2],
            ['producto_id' => $b->id, 'cantidad' => 9],
        ]))->toThrow(ValidationException::class);

        expect($a->fresh()->stock_actual)->toBe(5);
    });

    it('rechaza fechas de movimiento futuras', function () {
        $producto = Producto::factory()->create(['stock_actual' => 5]);

        expect(fn () => app(InventarioService::class)->adicionarStock($producto, 1, MovimientoInventario::TIPO_COMPRA, null, null, now()->addDay()))
            ->toThrow(ValidationException::class);
    });

    it('el Administrador registra una compra de varios productos desde el panel', function () {
        $this->seed(RoleSeeder::class);
        $this->actingAs(tap(User::factory()->create())->assignRole('Admin'));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $a = Producto::factory()->create(['stock_actual' => 5]);
        $b = Producto::factory()->create(['stock_actual' => 1]);

        Livewire::test(CreateMovimiento::class)
            ->fillForm([
                'tipo' => MovimientoInventario::TIPO_COMPRA,
                'fecha_movimiento' => now()->subHour()->format('Y-m-d H:i:00'),
                'lineas' => [
                    ['producto_id' => $a->id, 'cantidad' => 2],
                    ['producto_id' => $b->id, 'cantidad' => 3],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect($a->fresh()->stock_actual)->toBe(7)
            ->and($b->fresh()->stock_actual)->toBe(4);
    });
});

describe('RF07/RF08/RF09/RF11 reportes e indicadores OE4', function () {
    beforeEach(function () {
        $this->seed(MetodoPagoSeeder::class);
        $this->efectivo = MetodoPago::where('nombre', MetodoPago::EFECTIVO)->value('id');
        $this->nequi = MetodoPago::where('nombre', MetodoPago::NEQUI)->value('id');
        $this->producto = Producto::factory()->create(['nombre' => 'Limonada', 'precio_venta' => '5000.00', 'precio_costo' => '2000.00', 'stock_actual' => 20, 'stock_minimo' => 5]);
    });

    it('RF11: flujo diario = Base + Efectivo + Nequi - Gastos, con anuladas aparte', function () {
        app(CajaService::class)->abrir(['fecha' => today(), 'saldo_inicial' => '100000']);
        $ventas = app(VentaService::class);
        $ventas->crear(['metodo_pago_id' => $this->efectivo], [['producto_id' => $this->producto->id, 'cantidad' => 2]]);
        $ventas->crear(['metodo_pago_id' => $this->nequi], [['producto_id' => $this->producto->id, 'cantidad' => 1]]);
        $anulada = $ventas->crear(['metodo_pago_id' => $this->efectivo], [['producto_id' => $this->producto->id, 'cantidad' => 4]]);
        $ventas->anular($anulada, 'Error');
        Gasto::factory()->create(['fecha' => today(), 'monto' => 3000]);

        $flujo = app(ReporteService::class)->flujoCajaDiario(today());

        expect($flujo['base'])->toBe('100000.00')
            ->and($flujo['ventas']['efectivo'])->toBe('10000.00')
            ->and($flujo['ventas']['nequi'])->toBe('5000.00')
            ->and($flujo['anuladas_count'])->toBe(1)
            ->and($flujo['saldo_teorico'])->toBe('112000.00');
    });

    it('RF09: ventas diarias por producto agrupa por día y excluye anuladas', function () {
        $ventas = app(VentaService::class);
        $ventas->crear(['metodo_pago_id' => $this->efectivo, 'fecha_venta' => now()->subDay()], [['producto_id' => $this->producto->id, 'cantidad' => 2]]);
        $ventas->crear(['metodo_pago_id' => $this->efectivo], [['producto_id' => $this->producto->id, 'cantidad' => 3]]);
        $ventas->anular($ventas->crear(['metodo_pago_id' => $this->efectivo], [['producto_id' => $this->producto->id, 'cantidad' => 1]]), 'Error');

        $filas = app(ReporteService::class)->ventasDiariasPorProductoRango(now()->subDays(2), now());

        expect($filas)->toHaveCount(2)
            ->and($filas->pluck('cantidad')->map(fn ($c) => (int) $c)->all())->toBe([2, 3])
            ->and($filas->last()->fecha)->toBe(today()->toDateString());
    });

    it('OE4: indicadores de pérdidas, agotados y cuadre de caja', function () {
        app(InventarioService::class)->retirarStock($this->producto, 2, MovimientoInventario::TIPO_MERMA, 'Vencido');
        Producto::factory()->create(['stock_actual' => 0, 'activo' => true]);
        $caja = app(CajaService::class)->abrir(['fecha' => today(), 'saldo_inicial' => '50000']);
        app(CajaService::class)->cerrar($caja, ['saldo_real' => '48000']);

        $ind = app(ReporteService::class)->indicadores(today(), today());

        expect($ind['perdidas'])->toBe(['unidades' => 2, 'valor_costo' => '4000.00'])
            ->and($ind['agotados'])->toBe(1)
            ->and($ind['cuadre_caja']['con_diferencia'])->toBe(1)
            ->and($ind['cuadre_caja']['faltante_total'])->toBe('2000.00')
            ->and($ind['precision_inventario']['porcentaje'])->toBe(100.0);
    });

    it('RF07: sugerencia de reorden hasta el mínimo × factor configurable', function () {
        $producto = Producto::factory()->make(['stock_actual' => 3, 'stock_minimo' => 5]);

        expect($producto->sugerenciaReorden())->toBe(7);
        config()->set('mundococo.factor_reorden', 3);
        expect($producto->sugerenciaReorden())->toBe(12);
    });

    it('la página de reportes y el dashboard con alertas se renderizan con datos', function () {
        $this->seed(RoleSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        app(VentaService::class)->crear(['metodo_pago_id' => $this->nequi], [['producto_id' => $this->producto->id, 'cantidad' => 16]]);

        $this->actingAs(tap(User::factory()->create())->assignRole('Consultor'));
        Livewire::test(Reportes::class)
            ->assertOk()
            ->assertSee('Ventas diarias por producto')
            ->assertSee('Indicadores de gestión')
            ->assertSee('Limonada');

        $this->actingAs(tap(User::factory()->create())->assignRole('Operario'));
        Livewire::test(StockCritico::class)
            ->assertOk()
            ->assertSee('Limonada')
            ->assertSee('Reponer 6 uds');
    });
});

describe('Ley 1581: consentimiento informado y política de datos', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    });

    it('sin aceptar la política no puede operar: se le redirige al consentimiento', function () {
        $usuario = tap(User::factory()->sinConsentimiento()->create())->assignRole('Operario');

        $this->actingAs($usuario)->get('/admin')->assertRedirect(ConsentimientoDatos::getUrl());
        $this->actingAs($usuario)->get(ConsentimientoDatos::getUrl())->assertOk()->assertSee('autorizo el tratamiento');
    });

    it('al aceptar queda la fecha como prueba, se audita y ya puede operar', function () {
        $usuario = tap(User::factory()->sinConsentimiento()->create())->assignRole('Operario');
        $this->actingAs($usuario);

        Livewire::test(ConsentimientoDatos::class)->call('aceptar')->assertRedirect();

        expect($usuario->fresh()->politica_aceptada_at)->not->toBeNull()
            ->and(AuditLog::where('accion', 'politica_datos_aceptada')->where('modelo_id', $usuario->id)->exists())->toBeTrue();
        $this->get('/admin')->assertOk();
    });

    it('la política es pública y el login la enlaza', function () {
        $this->get('/privacidad')->assertOk()->assertSee('Ley 1581 de 2012')->assertSee('Derechos del titular');
        $this->get('/admin/login')->assertOk()->assertSee('política de tratamiento de datos personales');
    });
});

describe('RNF07 escalabilidad: sucursales e integración por API', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        $this->producto = Producto::factory()->create(['nombre' => 'Cocada', 'precio_venta' => '3000.00', 'stock_actual' => 9]);
    });

    it('toda operación queda ligada a la sucursal principal por defecto', function () {
        $metodo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $venta = app(VentaService::class)->crear(['metodo_pago_id' => $metodo->id], [['producto_id' => $this->producto->id, 'cantidad' => 1]]);
        $caja = app(CajaService::class)->abrir(['fecha' => today(), 'saldo_inicial' => '0']);

        $principal = Sucursal::principal();
        expect($principal->principal)->toBeTrue()
            ->and($venta->sucursal_id)->toBe($principal->id)
            ->and($caja->sucursal_id)->toBe($principal->id)
            ->and(MovimientoInventario::whereNull('sucursal_id')->count())->toBe(0);
    });

    it('la API exige token y responde según los permisos RF10 del dueño', function () {
        $this->getJson('/api/v1/productos')->assertUnauthorized();

        Sanctum::actingAs(tap(User::factory()->create())->assignRole('Operario'));
        $this->getJson('/api/v1/productos')->assertOk()->assertJsonFragment(['nombre' => 'Cocada', 'stock_actual' => 9]);
        $this->getJson('/api/v1/reportes/inventario')->assertForbidden();

        Sanctum::actingAs(tap(User::factory()->create())->assignRole('Consultor'));
        $this->getJson('/api/v1/productos')->assertForbidden();
        $this->getJson('/api/v1/reportes/inventario')->assertOk()->assertJsonPath('columnas.0', 'Categoría');
        $this->getJson('/api/v1/reportes/desconocido')->assertNotFound();
    });

    it('emite tokens por consola solo para usuarios con rol', function () {
        $admin = tap(User::factory()->create(['email' => 'admin@mundococo.test']))->assignRole('Admin');

        $this->artisan('mundococo:token-api', ['email' => 'admin@mundococo.test', '--nombre' => 'contabilidad'])->assertSuccessful();
        $this->artisan('mundococo:token-api', ['email' => 'nadie@mundococo.test'])->assertFailed();

        expect($admin->tokens()->where('name', 'contabilidad')->exists())->toBeTrue();
    });
});

describe('Pantallas del panel (humo)', function () {
    it('el Administrador abre todas las pantallas modificadas y el Operario no ve la auditoría', function () {
        $this->seed(RoleSeeder::class);
        $this->seed(MetodoPagoSeeder::class);
        $producto = Producto::factory()->create(['precio_venta' => '5000.00', 'stock_actual' => 3, 'stock_minimo' => 5]);
        $venta = app(VentaService::class)->crear(['metodo_pago_id' => MetodoPago::where('nombre', MetodoPago::NEQUI)->value('id')], [['producto_id' => $producto->id, 'cantidad' => 1]]);
        app(CajaService::class)->abrir(['fecha' => today(), 'saldo_inicial' => '0']);
        $admin = tap(User::factory()->create())->assignRole('Admin');

        $this->actingAs($admin);
        foreach (['/admin', '/admin/productos', '/admin/productos/create', '/admin/ventas', '/admin/ventas/create',
            "/admin/ventas/{$venta->id}/edit", '/admin/cajas', '/admin/cajas/create', '/admin/movimientos/movimiento-inventarios',
            '/admin/movimientos/movimiento-inventarios/create', '/admin/gastos', '/admin/categorias', '/admin/metodo-pagos',
            '/admin/users', '/admin/users/create', '/admin/roles', '/admin/audit-logs', '/admin/reportes', '/admin/profile'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->actingAs(tap(User::factory()->create())->assignRole('Operario'))->get('/admin/audit-logs')->assertForbidden();
    });
});

describe('Acceso al panel (RF10 + RNF04)', function () {
    it('un usuario sin rol no entra al panel; con rol sí', function () {
        $this->seed(RoleSeeder::class);
        $sinRol = User::factory()->create();

        $this->actingAs($sinRol)->get('/admin')->assertForbidden();
        $this->actingAs(tap(User::factory()->create())->assignRole('Consultor'))->get('/admin')->assertOk();
    });
});
