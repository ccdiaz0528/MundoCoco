<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Gasto;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * RF08 Reportes de Inventario + RF09 Reportes de Ventas
 * Provee datos para visualización y exportación PDF/Excel
 */
class ReporteService
{
    // RF08: Inventario actual por categoría
    public function inventarioPorCategoria(): Collection
    {
        return Categoria::withCount('productos')
            ->with(['productos' => fn ($q) => $q->where('activo', true)])
            ->get()
            ->map(fn (Categoria $cat) => [
                'categoria' => $cat->nombre,
                'descripcion' => $cat->descripcion,
                'total_productos' => $cat->productos_count,
                'stock_total' => $cat->productos->sum('stock_actual'),
                'productos_bajo' => $cat->productos->filter(fn ($p) => $p->stockBajo())->count(),
                'valorizacion_costo' => $cat->productos->sum(fn ($p) => (float) $p->precio_costo * $p->stock_actual),
                'valorizacion_venta' => $cat->productos->sum(fn ($p) => (float) $p->precio_venta * $p->stock_actual),
            ]);
    }

    // RF08: Productos con stock bajo
    public function productosStockBajo(): Collection
    {
        return Producto::whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('activo', true)
            ->with('categoria')
            ->orderBy('stock_actual')
            ->get();
    }

    // RF08: Valorización inventario (cantidad * precio) por categoría y global
    public function valorizacionInventario(?int $categoriaId = null): array
    {
        $q = Producto::where('activo', true);
        if ($categoriaId) {
            $q->where('categoria_id', $categoriaId);
        }
        $productos = $q->with('categoria')->get();

        return [
            'productos' => $productos,
            'total_costo' => $productos->sum(fn ($p) => (float) $p->precio_costo * $p->stock_actual),
            'total_venta' => $productos->sum(fn ($p) => (float) $p->precio_venta * $p->stock_actual),
            'ganancia_potencial' => $productos->sum(fn ($p) => ((float) $p->precio_venta - (float) $p->precio_costo) * $p->stock_actual),
            'count' => $productos->count(),
        ];
    }

    // RF08: Movimientos por período
    public function movimientosPorPeriodo(Carbon|string $desde, Carbon|string $hasta, ?int $productoId = null, ?string $tipo = null): Collection
    {
        $q = MovimientoInventario::with(['producto.categoria', 'user'])
            ->whereBetween('created_at', [Carbon::parse($desde)->startOfDay(), Carbon::parse($hasta)->endOfDay()])
            ->orderByDesc('created_at');

        if ($productoId) {
            $q->where('producto_id', $productoId);
        }
        if ($tipo) {
            $q->where('tipo', $tipo);
        }

        return $q->get();
    }

    // RF09: Ventas diarias por producto
    public function ventasDiariasPorProducto(Carbon|string $fecha): Collection
    {
        $fecha = Carbon::parse($fecha)->toDateString();

        return VentaDetalle::whereHas('venta', fn ($q) => $q->whereDate('fecha_venta', $fecha))
            ->with(['producto.categoria', 'venta.metodoPago'])
            ->select('producto_id', DB::raw('SUM(cantidad) as total_cantidad'), DB::raw('SUM(subtotal) as total_ingreso'), DB::raw('COUNT(*) as transacciones'))
            ->groupBy('producto_id')
            ->orderByDesc('total_ingreso')
            ->get();
    }

    // RF09: Ventas por categoría (agregado en SQL, no en memoria)
    public function ventasPorCategoria(Carbon|string $desde, Carbon|string $hasta): Collection
    {
        $desde = Carbon::parse($desde)->startOfDay();
        $hasta = Carbon::parse($hasta)->endOfDay();
        // Expresión repetida (no alias) para compatibilidad PostgreSQL.
        $categoria = "COALESCE(categorias.nombre, 'Sin categoría')";

        return VentaDetalle::query()
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->leftJoin('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->whereBetween('ventas.fecha_venta', [$desde, $hasta])
            ->selectRaw("{$categoria} as categoria")
            ->selectRaw('SUM(venta_detalles.cantidad) as cantidad_vendida')
            ->selectRaw('SUM(venta_detalles.subtotal) as ingreso_total')
            ->selectRaw('COUNT(*) as transacciones')
            ->groupBy(DB::raw($categoria))
            ->orderByDesc('ingreso_total')
            ->get();
    }

    // RF09: Productos más vendidos
    public function productosMasVendidos(int $limite = 10, ?Carbon $desde = null, ?Carbon $hasta = null): Collection
    {
        $q = VentaDetalle::with('producto.categoria');

        if ($desde && $hasta) {
            $q->whereHas('venta', fn ($qq) => $qq->whereBetween('fecha_venta', [$desde, $hasta]));
        }

        return $q->select('producto_id', DB::raw('SUM(cantidad) as total_cantidad'), DB::raw('SUM(subtotal) as total_ingreso'))
            ->groupBy('producto_id')
            ->orderByDesc('total_cantidad')
            ->limit($limite)
            ->get();
    }

    // RF09: Análisis ingresos por período (agrupado por día/mes)
    public function ingresosPorPeriodo(Carbon|string $desde, Carbon|string $hasta, string $agrupado = 'dia'): Collection
    {
        $desde = Carbon::parse($desde)->startOfDay();
        $hasta = Carbon::parse($hasta)->endOfDay();

        $format = $agrupado === 'mes' ? '%Y-%m' : '%Y-%m-%d';
        $driver = DB::connection()->getDriverName();

        // RNF08 portabilidad: SQLite, MySQL y PostgreSQL.
        $dateExpr = match ($driver) {
            'sqlite' => "strftime('{$format}', fecha_venta)",
            'pgsql' => $agrupado === 'mes'
                ? "to_char(fecha_venta, 'YYYY-MM')"
                : "to_char(fecha_venta, 'YYYY-MM-DD')",
            default => "DATE_FORMAT(fecha_venta, '{$format}')",
        };

        return Venta::whereBetween('fecha_venta', [$desde, $hasta])
            ->selectRaw("{$dateExpr} as periodo, SUM(total) as total, COUNT(*) as transacciones")
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();
    }

    // Comparativo ventas entre períodos
    public function comparativoVentas(Carbon $periodo1Desde, Carbon $periodo1Hasta, Carbon $periodo2Desde, Carbon $periodo2Hasta): array
    {
        $total1 = (float) Venta::whereBetween('fecha_venta', [$periodo1Desde, $periodo1Hasta])->sum('total');
        $total2 = (float) Venta::whereBetween('fecha_venta', [$periodo2Desde, $periodo2Hasta])->sum('total');
        $trans1 = Venta::whereBetween('fecha_venta', [$periodo1Desde, $periodo1Hasta])->count();
        $trans2 = Venta::whereBetween('fecha_venta', [$periodo2Desde, $periodo2Hasta])->count();

        $variacion = $total1 > 0 ? (($total2 - $total1) / $total1 * 100) : 0;

        return [
            'periodo1' => ['total' => $total1, 'transacciones' => $trans1, 'desde' => $periodo1Desde, 'hasta' => $periodo1Hasta],
            'periodo2' => ['total' => $total2, 'transacciones' => $trans2, 'desde' => $periodo2Desde, 'hasta' => $periodo2Hasta],
            'variacion_porcentual' => round($variacion, 2),
            'diferencia' => $total2 - $total1,
        ];
    }

    // Flujo de caja diario - conciliación financiera
    public function flujoCajaDiario(Carbon|string $fecha): array
    {
        $cajaService = app(CajaService::class);
        $totales = $cajaService->totalesPorFecha($fecha);
        $fecha = Carbon::parse($fecha);

        $ventasCount = Venta::whereDate('fecha_venta', $fecha)->count();
        $gastosCount = Gasto::whereDate('fecha', $fecha)->count();
        $gastosTotal = (float) Gasto::whereDate('fecha', $fecha)->sum('monto');

        return [
            'fecha' => $fecha->toDateString(),
            'ventas' => $totales,
            'gastos_total' => $gastosCount > 0 ? number_format($gastosTotal, 2, '.', '') : '0.00',
            'gastos_count' => $gastosCount,
            'transacciones' => $ventasCount,
            'saldo_teorico' => number_format((float) $totales['total'] - $gastosTotal, 2, '.', ''),
        ];
    }
}
