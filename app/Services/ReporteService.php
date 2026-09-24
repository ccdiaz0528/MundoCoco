<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Caja;
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
        // Agregado en SQL (no en memoria), igual que ventasPorCategoria.
        $fila = Producto::query()
            ->where('activo', true)
            ->when($categoriaId, fn ($q) => $q->where('categoria_id', $categoriaId))
            ->selectRaw('COALESCE(SUM(precio_costo * stock_actual), 0) as total_costo')
            ->selectRaw('COALESCE(SUM(precio_venta * stock_actual), 0) as total_venta')
            ->selectRaw('COUNT(*) as total_productos')
            ->toBase()
            ->first();

        $formato = fn ($valor): string => number_format((float) $valor, 2, '.', '');

        return [
            'total_costo' => $formato($fila->total_costo),
            'total_venta' => $formato($fila->total_venta),
            'ganancia_potencial' => $formato((float) $fila->total_venta - (float) $fila->total_costo),
            'count' => (int) $fila->total_productos,
        ];
    }

    // RF08: Movimientos por período
    public function movimientosPorPeriodo(Carbon|string $desde, Carbon|string $hasta, ?int $productoId = null, ?string $tipo = null): Collection
    {
        $q = MovimientoInventario::with(['producto.categoria', 'user'])
            ->whereBetween('fecha_movimiento', [Carbon::parse($desde)->startOfDay(), Carbon::parse($hasta)->endOfDay()])
            ->orderByDesc('fecha_movimiento');

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

        return VentaDetalle::whereHas('venta', fn ($q) => $q->vigentes()->whereDate('fecha_venta', $fecha))
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
            ->whereNull('ventas.anulada_at')
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
        $q = VentaDetalle::with('producto.categoria')
            ->whereHas('venta', fn ($qq) => $qq->vigentes()
                ->when($desde && $hasta, fn ($rango) => $rango->whereBetween('fecha_venta', [$desde, $hasta])));

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
        $dateExpr = $this->expresionFecha('fecha_venta', $agrupado);

        // GROUP BY/ORDER BY con la expresión (no el alias): SQL Server no acepta alias en GROUP BY.
        return Venta::vigentes()->whereBetween('fecha_venta', [$desde, $hasta])
            ->selectRaw("{$dateExpr} as periodo, SUM(total) as total, COUNT(*) as transacciones")
            ->groupBy(DB::raw($dateExpr))
            ->orderBy(DB::raw($dateExpr))
            ->get();
    }

    /**
     * RF09 "Ventas diarias por producto" en un rango: una fila por día y producto.
     *
     * @return Collection<int, object{fecha: string, producto: string, cantidad: int|string, ingreso: string, transacciones: int|string}>
     */
    public function ventasDiariasPorProductoRango(Carbon|string $desde, Carbon|string $hasta): Collection
    {
        $fecha = $this->expresionFecha('ventas.fecha_venta', 'dia');

        return DB::table('venta_detalles')
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->whereNull('ventas.anulada_at')
            ->whereBetween('ventas.fecha_venta', [Carbon::parse($desde)->startOfDay(), Carbon::parse($hasta)->endOfDay()])
            ->selectRaw("{$fecha} as fecha, productos.nombre as producto")
            ->selectRaw('SUM(venta_detalles.cantidad) as cantidad, SUM(venta_detalles.subtotal) as ingreso, COUNT(*) as transacciones')
            ->groupBy(DB::raw($fecha), 'productos.id', 'productos.nombre')
            ->orderBy(DB::raw($fecha))
            ->orderByDesc(DB::raw('SUM(venta_detalles.subtotal)'))
            ->get();
    }

    /**
     * RNF08 portabilidad: fecha como texto AAAA-MM-DD (o AAAA-MM) en SQLite,
     * MySQL/MariaDB, PostgreSQL y SQL Server.
     */
    public function expresionFecha(string $columna, string $agrupado = 'dia'): string
    {
        $mes = $agrupado === 'mes';

        return match (DB::connection()->getDriverName()) {
            'sqlite' => $mes ? "strftime('%Y-%m', {$columna})" : "strftime('%Y-%m-%d', {$columna})",
            'pgsql' => $mes ? "to_char({$columna}, 'YYYY-MM')" : "to_char({$columna}, 'YYYY-MM-DD')",
            'sqlsrv' => $mes ? "CONVERT(char(7), {$columna}, 126)" : "CONVERT(char(10), {$columna}, 23)",
            default => $mes ? "DATE_FORMAT({$columna}, '%Y-%m')" : "DATE_FORMAT({$columna}, '%Y-%m-%d')",
        };
    }

    // Comparativo ventas entre períodos
    public function comparativoVentas(Carbon $periodo1Desde, Carbon $periodo1Hasta, Carbon $periodo2Desde, Carbon $periodo2Hasta): array
    {
        $total1 = (float) Venta::vigentes()->whereBetween('fecha_venta', [$periodo1Desde, $periodo1Hasta])->sum('total');
        $total2 = (float) Venta::vigentes()->whereBetween('fecha_venta', [$periodo2Desde, $periodo2Hasta])->sum('total');
        $trans1 = Venta::vigentes()->whereBetween('fecha_venta', [$periodo1Desde, $periodo1Hasta])->count();
        $trans2 = Venta::vigentes()->whereBetween('fecha_venta', [$periodo2Desde, $periodo2Hasta])->count();

        $variacion = $total1 > 0 ? (($total2 - $total1) / $total1 * 100) : 0;

        return [
            'periodo1' => ['total' => $total1, 'transacciones' => $trans1, 'desde' => $periodo1Desde, 'hasta' => $periodo1Hasta],
            'periodo2' => ['total' => $total2, 'transacciones' => $trans2, 'desde' => $periodo2Desde, 'hasta' => $periodo2Hasta],
            'variacion_porcentual' => round($variacion, 2),
            'diferencia' => $total2 - $total1,
        ];
    }

    /**
     * RF11 "Reporte de flujo de efectivo diario":
     * Saldo = Base + Ventas_Efectivo + Ventas_Nequi (+ otros medios) - Gastos.
     * La base es el saldo inicial de la caja de ese día (0 si no se abrió).
     */
    public function flujoCajaDiario(Carbon|string $fecha): array
    {
        $totales = app(CajaService::class)->totalesPorFecha($fecha);
        $fecha = Carbon::parse($fecha);
        $caja = Caja::query()->whereDate('fecha', $fecha)->first();
        $base = (float) ($caja?->saldo_inicial ?? 0);
        $anuladas = Venta::query()->whereNotNull('anulada_at')->whereDate('fecha_venta', $fecha);

        return [
            'fecha' => $fecha->toDateString(),
            'caja' => $caja?->estado ?? 'sin abrir',
            'base' => number_format($base, 2, '.', ''),
            'ventas' => $totales,
            'gastos_total' => $totales['gastos'],
            'gastos_count' => Gasto::whereDate('fecha', $fecha)->count(),
            'transacciones' => Venta::vigentes()->whereDate('fecha_venta', $fecha)->count(),
            'anuladas_count' => (clone $anuladas)->count(),
            'anuladas_total' => number_format((float) (clone $anuladas)->sum('total'), 2, '.', ''),
            'saldo_teorico' => number_format($base + (float) $totales['total'] - (float) $totales['gastos'], 2, '.', ''),
            'saldo_real' => $caja?->estado === 'cerrada' ? (string) $caja->saldo_real : null,
            'diferencia' => $caja?->estado === 'cerrada' ? (string) $caja->diferencia : null,
        ];
    }

    /**
     * OE4: indicadores de validación del anteproyecto (precisión de
     * inventario, pérdidas, ventas perdidas por agotamiento, exactitud del
     * cuadre de caja) para el periodo indicado.
     */
    public function indicadores(Carbon|string $desde, Carbon|string $hasta): array
    {
        $desde = Carbon::parse($desde)->startOfDay();
        $hasta = Carbon::parse($hasta)->endOfDay();

        $totalProductos = Producto::count();
        $inconsistentes = app(InventarioService::class)->inconsistenciasStock()->count();

        $perdidas = DB::table('movimientos_inventario')
            ->join('productos', 'productos.id', '=', 'movimientos_inventario.producto_id')
            ->whereIn('movimientos_inventario.tipo', [MovimientoInventario::TIPO_MERMA, MovimientoInventario::TIPO_AJUSTE_NEGATIVO])
            ->whereBetween('movimientos_inventario.fecha_movimiento', [$desde, $hasta])
            ->selectRaw('COALESCE(SUM(movimientos_inventario.cantidad), 0) as unidades')
            ->selectRaw('COALESCE(SUM(movimientos_inventario.cantidad * COALESCE(productos.precio_costo, 0)), 0) as valor')
            ->first();

        // whereDate: el cast "date" puede guardarse con hora (00:00:00) según el motor.
        $cajas = Caja::query()->where('estado', 'cerrada')
            ->whereDate('fecha', '>=', $desde->toDateString())
            ->whereDate('fecha', '<=', $hasta->toDateString());
        $anuladas = Venta::query()->whereNotNull('anulada_at')->whereBetween('fecha_venta', [$desde, $hasta]);

        return [
            'precision_inventario' => [
                'productos' => $totalProductos,
                'inconsistentes' => $inconsistentes,
                'porcentaje' => $totalProductos > 0 ? round(($totalProductos - $inconsistentes) / $totalProductos * 100, 1) : 100.0,
            ],
            'perdidas' => [
                'unidades' => (int) $perdidas->unidades,
                'valor_costo' => number_format((float) $perdidas->valor, 2, '.', ''),
            ],
            'agotados' => Producto::where('activo', true)->where('stock_actual', '<=', 0)->count(),
            'cuadre_caja' => [
                'cerradas' => (clone $cajas)->count(),
                'con_diferencia' => (clone $cajas)->where('diferencia', '!=', 0)->count(),
                'faltante_total' => number_format(abs((float) (clone $cajas)->where('diferencia', '<', 0)->sum('diferencia')), 2, '.', ''),
                'sobrante_total' => number_format((float) (clone $cajas)->where('diferencia', '>', 0)->sum('diferencia'), 2, '.', ''),
            ],
            'anulaciones' => [
                'cantidad' => (clone $anuladas)->count(),
                'total' => number_format((float) (clone $anuladas)->sum('total'), 2, '.', ''),
            ],
            'ventas_precio_modificado' => AuditLog::where('accion', 'venta_precio_modificado')->whereBetween('created_at', [$desde, $hasta])->count(),
        ];
    }
}
