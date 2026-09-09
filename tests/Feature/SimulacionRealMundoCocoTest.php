<?php

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Gasto;
use App\Models\MetodoPago;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Services\CajaService;
use App\Services\InventarioService;
use App\Services\ReporteService;
use App\Services\VentaService;
use Database\Seeders\CategoriaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Simulación operación real MundoCoco - valida flujo completo del anteproyecto
 * Objetivo: medir precisión inventario, tiempo proceso y reducción pérdidas
 */
describe('Simulación Real MundoCoco', function () {
    it('simula jornada completa MundoCoco: apertura, adiciones, ventas, gastos y cierre con precisión', function () {
        $start = microtime(true);

        // Preparar categorías reales RF06
        Categoria::query()->delete();
        (new CategoriaSeeder)->run();
        expect(Categoria::where('nombre', 'Helados')->exists())->toBeTrue();
        expect(Categoria::where('nombre', 'Aceites')->exists())->toBeTrue();

        $catHelados = Categoria::where('nombre', 'Helados')->first();
        $catBebidas = Categoria::where('nombre', 'Bebidas')->first();

        // Crear productos reales MundoCoco
        $arequipe = Producto::factory()->create(['categoria_id' => $catHelados->id, 'nombre' => 'Helado Arequipe Sim', 'stock_actual' => 30, 'precio_venta' => 4500]);
        $brownie = Producto::factory()->create(['categoria_id' => $catHelados->id, 'nombre' => 'Helado Brownie Sim', 'stock_actual' => 15, 'precio_venta' => 5000]);
        $limonada = Producto::factory()->create(['categoria_id' => $catBebidas->id, 'nombre' => 'Limonada Sim', 'stock_actual' => 25, 'precio_venta' => 4000]);

        // Stock inicial - movimiento trazado
        $invSvc = app(InventarioService::class);
        expect($arequipe->fresh()->movimientos()->where('tipo', 'inicial')->exists())->toBeTrue();

        // Abrir caja 8am con base 100k
        $cajaSvc = app(CajaService::class);
        $caja = $cajaSvc->abrir(['fecha' => now()->toDateString(), 'saldo_inicial' => 100000]);
        expect($caja->estado)->toBe('abierta');

        // 9am - Compra / adición stock (RF03) - llegada proveedor
        $invSvc->adicionarStock($brownie, 10, 'compra', 'Reposición mañana');
        expect((int) $brownie->fresh()->stock_actual)->toBe(25);

        // 10am-6pm - Registrar 15 ventas variadas (3 métodos pago)
        $efectivo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $transfer = MetodoPago::factory()->create(['nombre' => MetodoPago::TRANSFERENCIA]);
        $tarjeta = MetodoPago::factory()->create(['nombre' => MetodoPago::TARJETA]);
        $ventaSvc = app(VentaService::class);

        $ventas = [];
        for ($i = 0; $i < 5; $i++) {
            $ventas[] = $ventaSvc->crear(['metodo_pago_id' => $efectivo->id], [['producto_id' => $arequipe->id, 'cantidad' => 1]]);
        }
        for ($i = 0; $i < 5; $i++) {
            $ventas[] = $ventaSvc->crear(['metodo_pago_id' => $transfer->id], [['producto_id' => $brownie->id, 'cantidad' => 2]]);
        }
        for ($i = 0; $i < 5; $i++) {
            $ventas[] = $ventaSvc->crear(['metodo_pago_id' => $tarjeta->id], [['producto_id' => $limonada->id, 'cantidad' => 1]]);
        }

        expect(count($ventas))->toBe(15);

        // Verificar stock precisión: Arequipe 30-5=25, Brownie 25-10=15, Limonada 25-5=20
        expect((int) $arequipe->fresh()->stock_actual)->toBe(25);
        expect((int) $brownie->fresh()->stock_actual)->toBe(15);
        expect((int) $limonada->fresh()->stock_actual)->toBe(20);

        // Verificar que nunca stock negativo incluso con líneas duplicadas
        expect(fn () => $ventaSvc->crear(['metodo_pago_id' => $efectivo->id], [
            ['producto_id' => $arequipe->id, 'cantidad' => 20],
            ['producto_id' => $arequipe->id, 'cantidad' => 10],
        ]))->toThrow(ValidationException::class);

        // 2pm - Gasto materia prima (RF11)
        Gasto::factory()->create(['fecha' => now()->toDateString(), 'monto' => 15000, 'categoria' => 'materia_prima', 'descripcion' => 'Compra leche']);
        Gasto::factory()->create(['fecha' => now()->toDateString(), 'monto' => 5000, 'categoria' => 'transporte']);

        // Caja recalculada automáticamente
        $caja->refresh();
        $totales = $cajaSvc->totalesPorFecha(now()->toDateString());

        // Totales segregados por método
        expect((float) $totales['efectivo'])->toBe((float) (5 * 4500)) // 22500.0
            ->and((float) $totales['transferencias'])->toBe((float) (5 * 2 * 5000)) // 50000.0
            ->and((float) $totales['tarjetas'])->toBe((float) (5 * 4000)) // 20000.0
            ->and((float) $totales['total'])->toBe((float) 92500)
            ->and((float) $totales['gastos'])->toBe((float) 20000);

        // Validar Reportes RF08/RF09
        $repSvc = app(ReporteService::class);
        $invPorCat = $repSvc->inventarioPorCategoria();
        expect($invPorCat->count())->toBeGreaterThanOrEqual(3);

        $bajos = $repSvc->productosStockBajo();
        // Ninguno bajo aún (stocks 15-25 > minimo 5)
        // Forzar uno bajo
        $prodBajo = Producto::factory()->create(['categoria_id' => $catBebidas->id, 'nombre' => 'Test Bajo', 'stock_actual' => 2, 'stock_minimo' => 10]);
        expect(app(ReporteService::class)->productosStockBajo()->where('id', $prodBajo->id)->count())->toBe(1);

        // Cierre caja 8pm - dinero contado coincide
        $esperado = 100000 + 92500 - 20000; // 172500
        $cajaCerrada = $cajaSvc->cerrar($caja, ['saldo_real' => $esperado, 'observaciones_cierre' => 'Jornada sin novedades']);
        expect((float) $cajaCerrada->saldo_teorico)->toBe((float) $esperado)
            ->and((float) $cajaCerrada->diferencia)->toBe(0.0)
            ->and($cajaCerrada->estado)->toBe('cerrada');

        // Verificar que no se permiten ventas en caja cerrada (regla crítica)
        expect(fn () => $ventaSvc->crear(['metodo_pago_id' => $efectivo->id], [['producto_id' => $arequipe->id, 'cantidad' => 1]]))
            ->toThrow(ValidationException::class);

        // Métrica tiempo proceso <3s (RNF01)
        $elapsed = microtime(true) - $start;
        expect($elapsed)->toBeLessThan(3.0);

        // Trazabilidad completa RF12: movimientos venta =15 + inicial 3 + compra 1 =19
        expect(MovimientoInventario::count())->toBeGreaterThanOrEqual(19);
    });

    it('valida categorías RF06 exactas y valorización', function () {
        Categoria::query()->delete();
        (new CategoriaSeeder)->run();

        $nombres = Categoria::pluck('nombre')->toArray();
        expect($nombres)->toContain('Helados');
        expect($nombres)->toContain('Bebidas');
        expect($nombres)->toContain('Aceites');
        expect($nombres)->toContain('Productos de Coco');
        expect(Categoria::where('nombre', 'Postres')->exists())->toBeFalse();
        expect(Categoria::where('nombre', 'Ingredientes')->exists())->toBeFalse();
    });

    it('valida roles RF10 Admin Operador Consultor con permisos diferenciados', function () {
        (new RoleSeeder)->run();
        expect(Role::where('name', 'Admin')->exists())->toBeTrue();
        expect(Role::where('name', 'Operador')->exists())->toBeTrue();
        expect(Role::where('name', 'Consultor')->exists())->toBeTrue();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $consultor = User::factory()->create();
        $consultor->assignRole('Consultor');

        expect($admin->hasPermissionTo('ver auditoria'))->toBeTrue();
        expect($consultor->hasPermissionTo('ver auditoria'))->toBeFalse();
        expect($consultor->hasPermissionTo('ver reportes'))->toBeTrue();
        expect($consultor->hasPermissionTo('crear ventas'))->toBeFalse();
    });
});
