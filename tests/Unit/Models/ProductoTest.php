<?php

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\VentaDetalle;

describe('Producto', function () {
    it('puede crear un producto', function () {
        $producto = Producto::factory()->create([
            'nombre' => 'Laptop',
            'precio_venta' => 1200.50,
        ]);

        expect($producto->nombre)->toBe('Laptop');
        expect($producto->precio_venta)->toBe(1200.50);
    });

    it('pertenece a una categoría', function () {
        $categoria = Categoria::factory()->create();
        $producto = Producto::factory()->create(['categoria_id' => $categoria->id]);

        expect($producto->categoria()->first()->id)->toBe($categoria->id);
    });

    it('detecta cuando el stock está bajo', function () {
        $productoConStockBajo = Producto::factory()->stockBajo()->create();
        $productoConStockNormal = Producto::factory()->create(['stock_actual' => 50, 'stock_minimo' => 10]);

        expect($productoConStockBajo->stockBajo())->toBeTrue();
        expect($productoConStockNormal->stockBajo())->toBeFalse();
    });

    it('puede tener muchos detalles de venta', function () {
        $producto = Producto::factory()->create();
        VentaDetalle::factory(5)->create(['producto_id' => $producto->id]);

        expect($producto->ventaDetalles()->count())->toBe(5);
    });

    it('solo muestra productos activos cuando se filtra', function () {
        Producto::factory()->inactivo()->create();
        Producto::factory(3)->create(['activo' => true]);

        $productosActivos = Producto::where('activo', true)->get();

        expect($productosActivos->count())->toBe(3);
    });

    it('calcula ganancia entre precio venta y costo', function () {
        $producto = Producto::factory()->create([
            'precio_costo' => 100,
            'precio_venta' => 150,
        ]);

        $ganancia = $producto->precio_venta - $producto->precio_costo;

        expect($ganancia)->toBe(50);
    });
});
