<?php

use App\Models\Categoria;
use App\Models\Producto;
use App\Services\InventarioService;
use App\Services\ReporteService;

/**
 * RNF01 Rendimiento: operaciones críticas por debajo de 3 segundos
 * con el volumen mínimo exigido (200 productos).
 */
describe('RNF01 rendimiento', function () {
    it('opera con 200 productos en menos de 3 segundos', function () {
        Producto::factory(200)->recycle(Categoria::factory(4)->create())->create();
        expect(Producto::count())->toBeGreaterThanOrEqual(200);

        $inicio = microtime(true);
        $svc = app(ReporteService::class);
        $svc->inventarioPorCategoria();
        $svc->productosStockBajo();
        $svc->valorizacionInventario();
        $duracion = microtime(true) - $inicio;

        expect($duracion)->toBeLessThan(3.0);
    });

    it('la valorización con 200 productos responde en menos de 3 segundos', function () {
        Producto::factory(200)->recycle(Categoria::factory(4)->create())->create();

        $inicio = microtime(true);
        app(InventarioService::class)->valorizacionInventario();
        $duracion = microtime(true) - $inicio;

        expect($duracion)->toBeLessThan(3.0);
    });
});
