<?php

namespace App\Services;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * RF08/RF09/RF11: todos los reportes se ven en pantalla y se exportan a PDF,
 * Excel y CSV. Los datos provienen de ReporteService/InventarioService; esta
 * clase solo los pone en forma de tabla.
 */
class ReporteExportService
{
    /** Tipo => título. RF08 (inventario), RF09 (ventas), RF11 (caja), OE4 (indicadores). */
    public const TIPOS = [
        'inventario' => 'Inventario actual por categoría',
        'stock_bajo' => 'Productos con stock bajo y sugerencia de reorden',
        'valorizacion' => 'Valorización de inventario',
        'movimientos' => 'Movimientos de inventario por período',
        'ventas' => 'Análisis de ingresos por período',
        'ventas_producto' => 'Ventas diarias por producto',
        'ventas_categoria' => 'Ventas por categoría',
        'mas_vendidos' => 'Productos más vendidos',
        'comparativo' => 'Comparativo de ventas entre períodos',
        'flujo_caja' => 'Flujo de efectivo diario',
        'indicadores' => 'Indicadores de gestión',
    ];

    public const FORMATOS = ['csv', 'pdf', 'xlsx'];

    public function __construct(private ReporteService $reportes) {}

    public function exportar(string $tipo, string $formato, string $desde, string $hasta): Response
    {
        abort_unless(array_key_exists($tipo, self::TIPOS), 404);
        abort_unless(in_array($formato, self::FORMATOS, true), 404);

        return match ($formato) {
            'pdf' => $this->pdf($tipo, $desde, $hasta),
            'xlsx' => $this->excel($tipo, $desde, $hasta),
            default => $this->csv($tipo, $desde, $hasta),
        };
    }

    private function csv(string $tipo, string $desde, string $hasta): StreamedResponse
    {
        [$encabezados, $filas, $archivo] = $this->datos($tipo, $desde, $hasta);

        return response()->stream(function () use ($encabezados, $filas): void {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM: Excel abre tildes correctamente
            fputcsv($salida, $encabezados);
            foreach ($filas as $fila) {
                fputcsv($salida, $fila);
            }
            fclose($salida);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$archivo}.csv\"",
        ]);
    }

    private function pdf(string $tipo, string $desde, string $hasta): Response
    {
        [$encabezados, $filas, $archivo] = $this->datos($tipo, $desde, $hasta);

        $opciones = new Options;
        $opciones->set('defaultFont', 'DejaVu Sans');
        $opciones->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($opciones);
        $dompdf->loadHtml(view('reportes.exportar-pdf', [
            'titulo' => self::TIPOS[$tipo],
            'desde' => $desde,
            'hasta' => $hasta,
            'generado' => Carbon::now()->format('d/m/Y H:i'),
            'encabezados' => $encabezados,
            'filas' => $filas,
        ])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$archivo}.pdf\"",
        ]);
    }

    private function excel(string $tipo, string $desde, string $hasta): StreamedResponse
    {
        [$encabezados, $filas, $archivo] = $this->datos($tipo, $desde, $hasta);

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle(Str::limit(Str::ascii(self::TIPOS[$tipo]), 28, ''));
        $hoja->fromArray($encabezados, null, 'A1');
        if ($filas !== []) {
            $hoja->fromArray($filas, null, 'A2');
        }
        $hoja->getStyle('A1:'.$hoja->getHighestColumn().'1')->getFont()->setBold(true);
        foreach (range('A', $hoja->getHighestColumn()) as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        $escritor = new Xlsx($libro);

        return new StreamedResponse(function () use ($escritor): void {
            $escritor->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$archivo}.xlsx\"",
        ]);
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<int, mixed>>, 2: string}
     */
    public function datos(string $tipo, string $desde, string $hasta): array
    {
        $dinero = fn ($valor): string => number_format((float) $valor, 2, '.', '');

        [$encabezados, $filas] = match ($tipo) {
            'inventario' => [
                ['Categoría', 'Productos', 'Stock total', 'Bajo stock', 'Val. costo', 'Val. venta'],
                $this->reportes->inventarioPorCategoria()->map(fn ($f) => [
                    $f['categoria'], $f['total_productos'], $f['stock_total'], $f['productos_bajo'], $dinero($f['valorizacion_costo']), $dinero($f['valorizacion_venta']),
                ])->all(),
            ],
            'stock_bajo' => [
                ['Código', 'Producto', 'Categoría', 'Stock', 'Mínimo', 'Sugerencia reorden'],
                $this->reportes->productosStockBajo()->map(fn ($p) => [
                    $p->codigo, $p->nombre, $p->categoria->nombre ?? '', $p->stock_actual, $p->stock_minimo, $p->sugerenciaReorden(),
                ])->all(),
            ],
            'valorizacion' => $this->filasValorizacion($dinero),
            'movimientos' => [
                ['Fecha', 'Registrado', 'Producto', 'Tipo', 'Cantidad', 'Stock anterior', 'Stock nuevo', 'Usuario', 'Motivo'],
                $this->reportes->movimientosPorPeriodo($desde, $hasta)->map(fn ($m) => [
                    $m->fecha_movimiento?->format('Y-m-d H:i'), $m->created_at?->format('Y-m-d H:i:s'), $m->producto->nombre ?? '', $m->tipo,
                    $m->cantidad, $m->stock_anterior, $m->stock_nuevo, $m->user->name ?? '', $m->motivo,
                ])->all(),
            ],
            'ventas' => [
                ['Período', 'Total', 'Transacciones'],
                $this->reportes->ingresosPorPeriodo($desde, $hasta, 'dia')->map(fn ($f) => [$f->periodo, $dinero($f->total), $f->transacciones])->all(),
            ],
            'ventas_producto' => [
                ['Fecha', 'Producto', 'Unidades', 'Ingreso', 'Líneas'],
                $this->reportes->ventasDiariasPorProductoRango($desde, $hasta)->map(fn ($f) => [$f->fecha, $f->producto, (int) $f->cantidad, $dinero($f->ingreso), (int) $f->transacciones])->all(),
            ],
            'ventas_categoria' => [
                ['Categoría', 'Unidades', 'Ingreso', 'Líneas'],
                $this->reportes->ventasPorCategoria($desde, $hasta)->map(fn ($f) => [$f['categoria'], (int) $f['cantidad_vendida'], $dinero($f['ingreso_total']), (int) $f['transacciones']])->all(),
            ],
            'mas_vendidos' => [
                ['Producto', 'Unidades', 'Ingreso'],
                $this->reportes->productosMasVendidos(20, Carbon::parse($desde)->startOfDay(), Carbon::parse($hasta)->endOfDay())
                    ->map(fn ($d) => [$d->producto->nombre ?? '', (int) $d->total_cantidad, $dinero($d->total_ingreso)])->all(),
            ],
            'comparativo' => $this->filasComparativo($desde, $hasta, $dinero),
            'flujo_caja' => $this->filasFlujo($hasta, $dinero),
            'indicadores' => $this->filasIndicadores($desde, $hasta),
        };

        return [$encabezados, array_values($filas), "{$tipo}_{$desde}_{$hasta}"];
    }

    /** @return array{0: array<int, string>, 1: array<int, array<int, mixed>>} */
    private function filasValorizacion(callable $dinero): array
    {
        $v = $this->reportes->valorizacionInventario();

        return [
            ['Concepto', 'Valor'],
            [
                ['Productos activos', $v['count']],
                ['Valorización a costo (cantidad × costo)', $dinero($v['total_costo'])],
                ['Valorización a venta (cantidad × precio)', $dinero($v['total_venta'])],
                ['Ganancia potencial', $dinero($v['ganancia_potencial'])],
            ],
        ];
    }

    /** @return array{0: array<int, string>, 1: array<int, array<int, mixed>>} */
    private function filasComparativo(string $desde, string $hasta, callable $dinero): array
    {
        [$anteriorDesde, $anteriorHasta] = self::periodoAnterior($desde, $hasta);
        $c = $this->reportes->comparativoVentas($anteriorDesde, $anteriorHasta, Carbon::parse($desde)->startOfDay(), Carbon::parse($hasta)->endOfDay());

        return [
            ['Período', 'Desde', 'Hasta', 'Total', 'Transacciones'],
            [
                ['Anterior', $anteriorDesde->toDateString(), $anteriorHasta->toDateString(), $dinero($c['periodo1']['total']), $c['periodo1']['transacciones']],
                ['Seleccionado', $desde, $hasta, $dinero($c['periodo2']['total']), $c['periodo2']['transacciones']],
                ['Diferencia', '', '', $dinero($c['diferencia']), ''],
                ['Variación %', '', '', $c['variacion_porcentual'], ''],
            ],
        ];
    }

    /** @return array{0: array<int, string>, 1: array<int, array<int, mixed>>} */
    private function filasFlujo(string $fecha, callable $dinero): array
    {
        $f = $this->reportes->flujoCajaDiario($fecha);

        return [
            ['Concepto', 'Valor'],
            [
                ['Fecha', $f['fecha']],
                ['Estado de la caja', $f['caja']],
                ['Base de caja', $dinero($f['base'])],
                ['Ventas en efectivo', $dinero($f['ventas']['efectivo'])],
                ['Ventas por Nequi', $dinero($f['ventas']['nequi'])],
                ['Ventas por transferencia', $dinero($f['ventas']['transferencias'])],
                ['Ventas con tarjeta', $dinero($f['ventas']['tarjetas'])],
                ['Total ventas ('.$f['transacciones'].')', $dinero($f['ventas']['total'])],
                ['Gastos y compras del día ('.$f['gastos_count'].')', $dinero($f['gastos_total'])],
                ['Ventas anuladas ('.$f['anuladas_count'].')', $dinero($f['anuladas_total'])],
                ['Saldo (Base + Ventas - Gastos)', $dinero($f['saldo_teorico'])],
                ['Saldo contado al cierre', $f['saldo_real'] ?? '—'],
                ['Diferencia', $f['diferencia'] ?? '—'],
            ],
        ];
    }

    /** @return array{0: array<int, string>, 1: array<int, array<int, mixed>>} */
    private function filasIndicadores(string $desde, string $hasta): array
    {
        $i = $this->reportes->indicadores($desde, $hasta);

        return [
            ['Indicador', 'Valor'],
            [
                ['Precisión de inventario (stock registrado = stock calculado)', $i['precision_inventario']['porcentaje'].' %'],
                ['Productos con inconsistencia', $i['precision_inventario']['inconsistentes'].' de '.$i['precision_inventario']['productos']],
                ['Pérdidas (mermas y ajustes negativos) - unidades', $i['perdidas']['unidades']],
                ['Pérdidas valorizadas a costo', $i['perdidas']['valor_costo']],
                ['Productos agotados (riesgo de venta perdida)', $i['agotados']],
                ['Cajas cerradas', $i['cuadre_caja']['cerradas']],
                ['Cajas con diferencia', $i['cuadre_caja']['con_diferencia']],
                ['Faltante total en caja', $i['cuadre_caja']['faltante_total']],
                ['Sobrante total en caja', $i['cuadre_caja']['sobrante_total']],
                ['Ventas anuladas', $i['anulaciones']['cantidad'].' ($ '.$i['anulaciones']['total'].')'],
                ['Ventas con precio distinto del base', $i['ventas_precio_modificado']],
            ],
        ];
    }

    /**
     * Período anterior de igual duración (RF09 comparativo).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function periodoAnterior(string $desde, string $hasta): array
    {
        $inicio = Carbon::parse($desde)->startOfDay();
        $largo = max(1, (int) $inicio->diffInDays(Carbon::parse($hasta)->startOfDay()) + 1);

        return [$inicio->copy()->subDays($largo), $inicio->copy()->subDay()->endOfDay()];
    }
}
