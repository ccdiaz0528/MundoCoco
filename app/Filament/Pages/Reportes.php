<?php

namespace App\Filament\Pages;

use App\Services\ReporteExportService;
use App\Services\ReporteService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Reportes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Reportes';

    protected static UnitEnum|string|null $navigationGroup = 'Informes';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Reportes MundoCoco';

    protected static ?string $slug = 'reportes';

    protected string $view = 'filament.pages.reportes';

    public ?string $desde = null;

    public ?string $hasta = null;

    public function mount(): void
    {
        $this->desde = Carbon::now()->subDays(30)->toDateString();
        $this->hasta = Carbon::now()->toDateString();
    }

    public function filtrar(): void
    {
        $this->validate([
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);
    }

    public function getViewData(): array
    {
        $svc = app(ReporteService::class);
        $desde = $this->desde ?: Carbon::now()->subDays(30)->toDateString();
        $hasta = $this->hasta ?: Carbon::now()->toDateString();

        [$anteriorDesde, $anteriorHasta] = ReporteExportService::periodoAnterior($desde, $hasta);

        return [
            'inventarioPorCategoria' => $svc->inventarioPorCategoria(),
            'productosStockBajo' => $svc->productosStockBajo(),
            'valorizacion' => $svc->valorizacionInventario(),
            'ventasPorCategoria' => $svc->ventasPorCategoria($desde, $hasta),
            'ventasDiariasPorProducto' => $svc->ventasDiariasPorProductoRango($desde, $hasta),
            'ingresosPorDia' => $svc->ingresosPorPeriodo($desde, $hasta, 'dia'),
            'productosMasVendidos' => $svc->productosMasVendidos(5, Carbon::parse($desde)->startOfDay(), Carbon::parse($hasta)->endOfDay()),
            'flujo' => $svc->flujoCajaDiario($hasta),
            'indicadores' => $svc->indicadores($desde, $hasta),
            'movimientos' => $svc->movimientosPorPeriodo($desde, $hasta),
            'comparativo' => $svc->comparativoVentas($anteriorDesde, $anteriorHasta, Carbon::parse($desde)->startOfDay(), Carbon::parse($hasta)->endOfDay()),
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ver reportes') ?? false;
    }
}
