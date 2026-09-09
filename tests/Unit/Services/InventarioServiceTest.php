<?php

use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Validation\ValidationException;

describe('InventarioService RF02/RF03/RF05/RF12', function () {
    it('RF02 registra inventario inicial con trazabilidad', function () {
        $producto = Producto::factory()->create(['stock_actual' => 10, 'stock_minimo' => 5]);
        $svc = app(InventarioService::class);

        $mov = $svc->registrarInicial($producto, 25, 'Inventario inicial test');

        expect($mov->tipo)->toBe(MovimientoInventario::TIPO_INICIAL)
            ->and((int) $mov->cantidad)->toBe(25)
            ->and((int) $mov->stock_anterior)->toBe(10)
            ->and((int) $mov->stock_nuevo)->toBe(25)
            ->and((int) $producto->fresh()->stock_actual)->toBe(25);
    });

    it('RF03 adiciona stock por compra con validación positiva', function () {
        $producto = Producto::factory()->create(['stock_actual' => 20]);
        $svc = app(InventarioService::class);

        $mov = $svc->adicionarStock($producto, 15, MovimientoInventario::TIPO_COMPRA, 'Compra proveedor', 'Factura 001');

        expect($mov->tipo)->toBe('compra')
            ->and((int) $mov->cantidad)->toBe(15)
            ->and((int) $mov->stock_anterior)->toBe(20)
            ->and((int) $mov->stock_nuevo)->toBe(35)
            ->and((int) $producto->fresh()->stock_actual)->toBe(35);
    });

    it('RF03 rechaza cantidades no positivas', function () {
        $producto = Producto::factory()->create(['stock_actual' => 10]);
        $svc = app(InventarioService::class);

        expect(fn () => $svc->adicionarStock($producto, 0, MovimientoInventario::TIPO_COMPRA))
            ->toThrow(ValidationException::class);
        expect(fn () => $svc->adicionarStock($producto, -5, MovimientoInventario::TIPO_COMPRA))
            ->toThrow(ValidationException::class);
    });

    it('RF03/12 registra devolución y trazabilidad completa', function () {
        $producto = Producto::factory()->create(['stock_actual' => 5]);
        $svc = app(InventarioService::class);
        $inicialCount = $producto->fresh()->movimientos()->count(); // 1 por observer inicial

        $mov = $svc->adicionarStock($producto, 3, MovimientoInventario::TIPO_DEVOLUCION, 'Devolución cliente');

        expect($mov->tipo)->toBe('devolucion')
            ->and($producto->fresh()->movimientos()->count())->toBe($inicialCount + 1)
            ->and($producto->fresh()->movimientos()->where('tipo', 'devolucion')->count())->toBe(1);
    });

    it('retira stock por merma y valida insuficiente', function () {
        $producto = Producto::factory()->create(['stock_actual' => 4]);
        $svc = app(InventarioService::class);

        expect(fn () => $svc->retirarStock($producto, 10, MovimientoInventario::TIPO_MERMA))
            ->toThrow(ValidationException::class);

        $mov = $svc->retirarStock($producto, 2, MovimientoInventario::TIPO_MERMA, 'Producto vencido');
        expect((int) $mov->stock_nuevo)->toBe(2)
            ->and((int) $producto->fresh()->stock_actual)->toBe(2);
    });

    it('RF05 calcula stock total inicial + entradas - salidas', function () {
        $producto = Producto::factory()->create(['stock_actual' => 0]);
        $svc = app(InventarioService::class);

        $svc->registrarInicial($producto, 20);
        $svc->adicionarStock($producto->fresh(), 10, MovimientoInventario::TIPO_COMPRA);
        // Simular venta directa via movimiento
        $svc->retirarStock($producto->fresh(), 5, MovimientoInventario::TIPO_MERMA);

        $total = $svc->calcularStockTotal($producto->fresh());
        // Si hay inicial: 20 + 10 -5 =25 debe coincidir con stock_actual
        expect($total)->toBe(25)
            ->and((int) $producto->fresh()->stock_actual)->toBe(25);
    });

    it('RF08 valorización inventario correcta por categoría', function () {
        $cat = Categoria::factory()->create();
        Producto::factory()->create(['categoria_id' => $cat->id, 'precio_costo' => 100, 'precio_venta' => 150, 'stock_actual' => 10]);
        Producto::factory()->create(['categoria_id' => $cat->id, 'precio_costo' => 200, 'precio_venta' => 300, 'stock_actual' => 5]);

        $svc = app(InventarioService::class);
        $val = $svc->valorizacionInventario($cat->id);

        expect((float) $val['total_costo'])->toBe((float) (100 * 10 + 200 * 5)) // 2000.0
            ->and((float) $val['total_venta'])->toBe((float) (150 * 10 + 300 * 5)); // 3000.0
    });
});
