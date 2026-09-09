<?php

namespace App\Filament\Pages;

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
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
        ]);
    }

    public function getViewData(): array
    {
        $svc = app(ReporteService::class);
        $desde = $this->desde ?: Carbon::now()->subDays(30)->toDateString();
        $hasta = $this->hasta ?: Carbon::now()->toDateString();

        // RF09: el comparativo contrasta el periodo seleccionado con el
        // periodo anterior de igual duración.
        $largo = max(1, Carbon::parse($desde)->diffInDays(Carbon::parse($hasta)) + 1);
        $anteriorDesde = Carbon::parse($desde)->subDays($largo);
        $anteriorHasta = Carbon::parse($desde)->subDay();

        return [
            'inventarioPorCategoria' => $svc->inventarioPorCategoria(),
            'productosStockBajo' => $svc->productosStockBajo(),
            'valorizacion' => $svc->valorizacionInventario(),
            'ventasPorCategoria' => $svc->ventasPorCategoria($desde, $hasta),
            'ingresosPorDia' => $svc->ingresosPorPeriodo($desde, $hasta, 'dia'),
            'productosMasVendidos' => $svc->productosMasVendidos(5, Carbon::parse($desde), Carbon::parse($hasta)),
            'flujoHoy' => $svc->flujoCajaDiario(Carbon::now()),
            'movimientos' => $svc->movimientosPorPeriodo($desde, $hasta),
            'comparativo' => $svc->comparativoVentas($anteriorDesde, $anteriorHasta, Carbon::parse($desde), Carbon::parse($hasta)),
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->hasRole(['Admin', 'Operador', 'Consultor']);
    }
}
