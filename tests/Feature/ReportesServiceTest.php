<?php

use App\Models\Categoria;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\ReporteService;
use Carbon\Carbon;

describe('Reportes RF08 RF09', function () {
    beforeEach(function () {
        MetodoPago::factory()->create(['nombre' => MetodoPago::EFECTIVO]);
        MetodoPago::factory()->create(['nombre' => MetodoPago::TRANSFERENCIA]);
    });

    it('RF08 inventario por categoría con valorización', function () {
        $catHelados = Categoria::factory()->create(['nombre' => 'Helados']);
        $catBebidas = Categoria::factory()->create(['nombre' => 'Bebidas']);
        Producto::factory()->create(['categoria_id' => $catHelados->id, 'stock_actual' => 10, 'stock_minimo' => 5, 'precio_costo' => 100, 'precio_venta' => 150]);
        Producto::factory()->create(['categoria_id' => $catHelados->id, 'stock_actual' => 5, 'stock_minimo' => 10, 'precio_costo' => 200, 'precio_venta' => 300]);
        Producto::factory()->create(['categoria_id' => $catBebidas->id, 'stock_actual' => 20, 'stock_minimo' => 5, 'precio_costo' => 50, 'precio_venta' => 80]);

        $svc = app(ReporteService::class);
        $data = $svc->inventarioPorCategoria();

        expect($data->where('categoria', 'Helados')->first()['total_productos'])->toBe(2)
            ->and($data->where('categoria', 'Helados')->first()['productos_bajo'])->toBe(1)
            ->and($data->where('categoria', 'Bebidas')->first()['stock_total'])->toBe(20);
    });

    it('RF08 productos stock bajo lista correcta', function () {
        Producto::factory(2)->stockBajo()->create();
        Producto::factory(3)->create(['stock_actual' => 50, 'stock_minimo' => 10]);

        $svc = app(ReporteService::class);
        $bajos = $svc->productosStockBajo();
        expect($bajos->count())->toBe(2);
    });

    it('RF09 ventas diarias por producto', function () {
        $cat = Categoria::factory()->create();
        $p1 = Producto::factory()->create(['categoria_id' => $cat->id, 'precio_venta' => 100]);
        $p2 = Producto::factory()->create(['categoria_id' => $cat->id, 'precio_venta' => 200]);
        $met = MetodoPago::where('nombre', 'Efectivo')->first();
        $fecha = now()->toDateString();

        $v = Venta::factory()->create(['metodo_pago_id' => $met->id, 'fecha_venta' => $fecha, 'total' => 300]);
        VentaDetalle::factory()->create(['venta_id' => $v->id, 'producto_id' => $p1->id, 'cantidad' => 1, 'precio_unitario' => 100, 'subtotal' => 100]);
        VentaDetalle::factory()->create(['venta_id' => $v->id, 'producto_id' => $p2->id, 'cantidad' => 1, 'precio_unitario' => 200, 'subtotal' => 200]);

        $svc = app(ReporteService::class);
        $res = $svc->ventasDiariasPorProducto($fecha);
        expect($res->count())->toBe(2);
    });

    it('RF09 productos más vendidos ordenados', function () {
        $cat = Categoria::factory()->create();
        $p1 = Producto::factory()->create(['categoria_id' => $cat->id]);
        $p2 = Producto::factory()->create(['categoria_id' => $cat->id]);
        $met = MetodoPago::where('nombre', 'Efectivo')->first();

        // p1 vende 10 unidades en 2 ventas, p2 vende 1
        foreach (range(1, 2) as $i) {
            $v = Venta::factory()->create(['metodo_pago_id' => $met->id, 'total' => 100]);
            VentaDetalle::factory()->create(['venta_id' => $v->id, 'producto_id' => $p1->id, 'cantidad' => 5, 'subtotal' => 500]);
        }
        $v = Venta::factory()->create(['metodo_pago_id' => $met->id, 'total' => 50]);
        VentaDetalle::factory()->create(['venta_id' => $v->id, 'producto_id' => $p2->id, 'cantidad' => 1, 'subtotal' => 50]);

        $svc = app(ReporteService::class);
        $top = $svc->productosMasVendidos(1);
        expect($top->first()->producto_id)->toBe($p1->id)
            ->and((int) $top->first()->total_cantidad)->toBe(10);
    });

    it('RF09 ingresos por período agrupado día', function () {
        $met = MetodoPago::where('nombre', 'Efectivo')->first();
        $hoy = Carbon::today();
        $ayer = Carbon::yesterday();

        Venta::factory()->create(['metodo_pago_id' => $met->id, 'total' => 100, 'fecha_venta' => $hoy]);
        Venta::factory()->create(['metodo_pago_id' => $met->id, 'total' => 200, 'fecha_venta' => $ayer]);

        $svc = app(ReporteService::class);
        $res = $svc->ingresosPorPeriodo($ayer, $hoy, 'dia');
        expect($res->count())->toBe(2)
            ->and((float) $res->firstWhere('periodo', $ayer->format('Y-m-d'))['total'])->toBe(200.0);
    });

    it('RF09 comparativo entre períodos calcula variación', function () {
        $met = MetodoPago::where('nombre', 'Efectivo')->first();

        Venta::factory()->create(['metodo_pago_id' => $met->id, 'total' => 100, 'fecha_venta' => Carbon::today()->subDays(10)]);
        Venta::factory()->create(['metodo_pago_id' => $met->id, 'total' => 150, 'fecha_venta' => Carbon::today()]);

        $svc = app(ReporteService::class);
        $res = $svc->comparativoVentas(
            Carbon::today()->subDays(11), Carbon::today()->subDays(9),
            Carbon::today()->subDays(1), Carbon::today()
        );

        expect((float) $res['periodo1']['total'])->toBe(100.0)
            ->and((float) $res['periodo2']['total'])->toBe(150.0)
            ->and((float) $res['variacion_porcentual'])->toBe(50.0)
            ->and((float) $res['diferencia'])->toBe(50.0);
    });

    it('RF08 movimientos por período filtra por fechas', function () {
        $producto = Producto::factory()->create();
        $svc = app(ReporteService::class);

        $dentro = $svc->movimientosPorPeriodo(Carbon::today()->subDays(2), Carbon::today(), $producto->id);
        $fuera = $svc->movimientosPorPeriodo(Carbon::today()->subDays(30), Carbon::today()->subDays(20), $producto->id);

        expect($dentro->count())->toBeGreaterThanOrEqual(1)
            ->and($fuera->count())->toBe(0);
    });

    it('RF09 ventas por categoría agregadas', function () {
        $cat = Categoria::factory()->create(['nombre' => 'Helados']);
        $producto = Producto::factory()->create(['categoria_id' => $cat->id, 'precio_venta' => 100]);
        $met = MetodoPago::where('nombre', 'Efectivo')->first();

        $venta = Venta::factory()->create(['metodo_pago_id' => $met->id, 'total' => 300, 'fecha_venta' => now()]);
        VentaDetalle::factory()->create(['venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => 3, 'precio_unitario' => 100, 'subtotal' => 300]);

        $res = app(ReporteService::class)->ventasPorCategoria(now()->subDay(), now()->addDay());

        expect($res->count())->toBe(1)
            ->and($res->first()['categoria'])->toBe('Helados')
            ->and((int) $res->first()['cantidad_vendida'])->toBe(3)
            ->and((float) $res->first()['ingreso_total'])->toBe(300.0)
            ->and((int) $res->first()['transacciones'])->toBe(1);
    });
});
