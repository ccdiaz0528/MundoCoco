<?php

namespace App\Services;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * RF08/RF09: exportación de reportes en pantalla (CSV), PDF y Excel.
 * Los datos provienen de ReporteService; esta clase solo presenta formatos.
 */
class ReporteExportService
{
    public const TIPOS = ['inventario', 'ventas'];

    public const FORMATOS = ['csv', 'pdf', 'xlsx'];

    public function __construct(private ReporteService $reportes) {}

    public function exportar(string $tipo, string $formato, string $desde, string $hasta): Response
    {
        abort_unless(in_array($tipo, self::TIPOS, true), 404);
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
            fputcsv($salida, $encabezados);
            foreach ($filas as $fila) {
                fputcsv($salida, $fila);
            }
            fclose($salida);
        }, 200, [
            'Content-Type' => 'text/csv',
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
            'titulo' => $tipo === 'inventario' ? 'Reporte de inventario por categoría' : 'Reporte de ventas por día',
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
        $hoja->setTitle($tipo === 'inventario' ? 'Inventario' : 'Ventas');
        $hoja->fromArray($encabezados, null, 'A1');
        $hoja->fromArray($filas, null, 'A2');
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
    private function datos(string $tipo, string $desde, string $hasta): array
    {
        if ($tipo === 'inventario') {
            $datos = $this->reportes->inventarioPorCategoria();
            $filas = [];
            foreach ($datos as $fila) {
                $filas[] = [$fila['categoria'], $fila['total_productos'], $fila['stock_total'], $fila['productos_bajo'], $fila['valorizacion_costo'], $fila['valorizacion_venta']];
            }

            return [
                ['Categoria', 'Productos', 'Stock Total', 'Bajo Stock', 'Val Costo', 'Val Venta'],
                $filas,
                "inventario_{$desde}_{$hasta}",
            ];
        }

        $datos = $this->reportes->ingresosPorPeriodo($desde, $hasta, 'dia');
        $filas = [];
        foreach ($datos as $fila) {
            $filas[] = [$fila->periodo, $fila->total, $fila->transacciones];
        }

        return [
            ['Periodo', 'Total', 'Transacciones'],
            $filas,
            "ventas_{$desde}_{$hasta}",
        ];
    }
}
