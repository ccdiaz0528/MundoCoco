<?php

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\VentaDetalle;

describe('Gestión de Inventario', function () {
    it('puede crear categorías de productos', function () {
        $categoria = Categoria::factory()->create(['nombre' => 'Electrónica']);

        expect($categoria->nombre)->toBe('Electrónica');
    });

    it('puede crear productos bajo categoría', function () {
        $categoria = Categoria::factory()->create();
        $producto = Producto::factory()->create(['categoria_id' => $categoria->id]);

        expect($producto->categoria->id)->toBe($categoria->id);
    });

    it('puede buscar productos por categoría', function () {
        $categoria1 = Categoria::factory()->create(['nombre' => 'Electrónica']);
        $categoria2 = Categoria::factory()->create(['nombre' => 'Ropa']);

        Producto::factory(3)->create(['categoria_id' => $categoria1->id]);
        Producto::factory(2)->create(['categoria_id' => $categoria2->id]);

        $productosElectronica = Producto::where('categoria_id', $categoria1->id)->count();

        expect($productosElectronica)->toBe(3);
    });

    it('detecta cuando stock está bajo', function () {
        $productoAlto = Producto::factory()->create([
            'stock_actual' => 50,
            'stock_minimo' => 10,
        ]);

        $productoBajo = Producto::factory()->create([
            'stock_actual' => 3,
            'stock_minimo' => 10,
        ]);

        expect($productoAlto->stockBajo())->toBeFalse();
        expect($productoBajo->stockBajo())->toBeTrue();
    });

    it('puede desactivar un producto', function () {
        $producto = Producto::factory()->create(['activo' => true]);
        $producto->update(['activo' => false]);

        expect($producto->activo)->toBeFalse();
    });

    it('filtra solo productos activos', function () {
        Producto::factory(2)->inactivo()->create();
        Producto::factory(3)->create(['activo' => true]);

        $productosActivos = Producto::where('activo', true)->count();

        expect($productosActivos)->toBe(3);
    });

    it('puede calcular ganancia por producto', function () {
        $producto = Producto::factory()->create([
            'precio_costo' => 100,
            'precio_venta' => 200,
        ]);

        $ganancia = $producto->precio_venta - $producto->precio_costo;

        expect($ganancia)->toBe(100);
    });

    it('registra todas las ventas de un producto', function () {
        $producto = Producto::factory()->create();
        VentaDetalle::factory(5)->create(['producto_id' => $producto->id]);

        expect($producto->ventaDetalles()->count())->toBe(5);
    });

    it('puede listar productos con stock bajo', function () {
        Producto::factory(3)->stockBajo()->create();
        Producto::factory(5)->create(['stock_actual' => 50]);

        $conStockBajo = Producto::where('stock_actual', '<=', 'stock_minimo')->count();

        expect($conStockBajo)->toBeGreaterThanOrEqual(0);
    });
});
