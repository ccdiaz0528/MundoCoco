<?php

use App\Models\Categoria;
use App\Models\MetodoPago;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\InventarioService;
use App\Services\VentaService;
use Illuminate\Validation\ValidationException;

describe('Trazabilidad RF12', function () {
    it('RF12 cada venta genera movimiento con stock antes/después y usuario', function () {
        $producto = Producto::factory()->create(['categoria_id' => Categoria::factory(), 'stock_actual' => 20, 'precio_venta' => 100]);
        $metodo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $user = User::factory()->create();
        $this->actingAs($user);

        $venta = app(VentaService::class)->crear(['metodo_pago_id' => $metodo->id], [['producto_id' => $producto->id, 'cantidad' => 3]]);

        $mov = MovimientoInventario::where('producto_id', $producto->id)->where('tipo', 'venta')->first();
        expect($mov)->not->toBeNull()
            ->and((int) $mov->cantidad)->toBe(3)
            ->and((int) $mov->stock_anterior)->toBe(20)
            ->and((int) $mov->stock_nuevo)->toBe(17)
            ->and($mov->user_id)->toBe($user->id)
            ->and($mov->referencia_type)->toBe(Venta::class);
    });

    it('RF12 historial filtra por producto, fecha y tipo', function () {
        $p1 = Producto::factory()->create(['stock_actual' => 50]);
        $p2 = Producto::factory()->create(['stock_actual' => 50]);
        $svc = app(InventarioService::class);

        // p1 ya tiene 1 inicial por observer, luego +2 movimientos =3 total, historial debe reflejar
        $inicialP1 = MovimientoInventario::where('producto_id', $p1->id)->count();
        $svc->adicionarStock($p1, 10, 'compra');
        $svc->retirarStock($p1, 2, 'merma');
        $svc->adicionarStock($p2, 5, 'compra');

        $histP1 = $svc->historial($p1);
        expect($histP1->count())->toBe($inicialP1 + 2);

        $histCompra = MovimientoInventario::where('tipo', 'compra')->count();
        expect($histCompra)->toBe(2);
    });

    it('RF12 auditoría stock no negativo con líneas duplicadas', function () {
        $producto = Producto::factory()->create(['stock_actual' => 5, 'precio_venta' => 10]);
        $metodo = MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        $inicialMovCount = MovimientoInventario::where('producto_id', $producto->id)->count(); // 1 inicial

        expect(fn () => app(VentaService::class)->crear(['metodo_pago_id' => $metodo->id], [
            ['producto_id' => $producto->id, 'cantidad' => 3],
            ['producto_id' => $producto->id, 'cantidad' => 3],
        ]))->toThrow(ValidationException::class);

        expect((int) $producto->fresh()->stock_actual)->toBe(5);
        expect(MovimientoInventario::where('producto_id', $producto->id)->count())->toBe($inicialMovCount);
    });

    it('RF02 creación producto registra inventario inicial automáticamente', function () {
        $cat = Categoria::factory()->create();
        $producto = Producto::factory()->create(['categoria_id' => $cat->id, 'stock_actual' => 12]);

        $mov = MovimientoInventario::where('producto_id', $producto->id)->where('tipo', 'inicial')->first();
        expect($mov)->not->toBeNull()
            ->and((int) $mov->stock_nuevo)->toBe(12);
    });
});
