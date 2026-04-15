<?php

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\MetodoPago;
use App\Models\Caja;

describe('Flujo de Venta Completo', function () {
    beforeEach(function () {
        // Crear datos iniciales
        MetodoPago::factory()->create(['nombre' => 'Efectivo']);
        MetodoPago::factory()->create(['nombre' => 'Transferencia']);
    });

    it('puede crear una venta con múltiples productos', function () {
        // Crear categoría y productos
        $categoria = Categoria::factory()->create(['nombre' => 'Electrónica']);
        $producto1 = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Laptop',
            'precio_venta' => 1000,
            'stock_actual' => 10,
        ]);
        $producto2 = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Mouse',
            'precio_venta' => 25,
            'stock_actual' => 50,
        ]);

        // Crear venta
        $metodoPago = MetodoPago::where('nombre', 'Efectivo')->first();
        $venta = Venta::factory()->create([
            'metodo_pago_id' => $metodoPago->id,
            'total' => 1050,
        ]);

        // Agregar detalles
        VentaDetalle::factory()->create([
            'venta_id' => $venta->id,
            'producto_id' => $producto1->id,
            'cantidad' => 1,
            'precio_unitario' => 1000,
            'subtotal' => 1000,
        ]);

        VentaDetalle::factory()->create([
            'venta_id' => $venta->id,
            'producto_id' => $producto2->id,
            'cantidad' => 1,
            'precio_unitario' => 25,
            'subtotal' => 25,
        ]);

        // Verificaciones
        expect($venta->detalles()->count())->toBe(2);
        expect($venta->detalles()->sum('subtotal'))->toBe(1025);
    });

    it('puede registrar venta por diferentes métodos de pago', function () {
        $efectivo = MetodoPago::where('nombre', 'Efectivo')->first();
        $transferencia = MetodoPago::where('nombre', 'Transferencia')->first();

        $ventaEfectivo = Venta::factory()->create([
            'metodo_pago_id' => $efectivo->id,
            'total' => 100,
        ]);

        $ventaTransferencia = Venta::factory()->create([
            'metodo_pago_id' => $transferencia->id,
            'total' => 200,
        ]);

        expect($ventaEfectivo->metodoPago->nombre)->toBe('Efectivo');
        expect($ventaTransferencia->metodoPago->nombre)->toBe('Transferencia');
    });

    it('puede abrir y registrar ventas en una caja', function () {
        // Abrir caja
        $caja = Caja::factory()->create([
            'fecha' => now()->toDateString(),
            'estado' => 'abierta',
            'saldo_inicial' => 1000,
        ]);

        $efectivo = MetodoPago::where('nombre', 'Efectivo')->first();

        // Crear varias ventas
        Venta::factory(3)->create([
            'metodo_pago_id' => $efectivo->id,
            'total' => 100,
            'created_at' => now(),
        ]);

        expect($caja->estado)->toBe('abierta');
        expect(Venta::count())->toBeGreaterThanOrEqual(3);
    });

    it('puede detectar productos con stock bajo', function () {
        $categoria = Categoria::factory()->create();
        $productoBajo = Producto::factory()->stockBajo()->create([
            'categoria_id' => $categoria->id,
        ]);
        $productoNormal = Producto::factory()->create([
            'categoria_id' => $categoria->id,
        ]);

        expect($productoBajo->stockBajo())->toBeTrue();
        expect($productoNormal->stockBajo())->toBeFalse();
    });

    it('puede listar productos por categoría', function () {
        $categoria = Categoria::factory()->create(['nombre' => 'Ropa']);
        Producto::factory(5)->create(['categoria_id' => $categoria->id]);

        $productos = Producto::where('categoria_id', $categoria->id)->get();

        expect($productos->count())->toBe(5);
    });
});
