<?php

namespace App\Filament\Widgets;

use App\Models\Gasto;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\CajaService;
use App\Services\InventarioService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Ventas de hoy
        $ventasHoy = Venta::vigentes()->whereDate('fecha_venta', today())->sum('total');

        // Ventas del mes actual
        $ventasMes = Venta::vigentes()->whereMonth('fecha_venta', now()->month)
            ->whereYear('fecha_venta', now()->year)
            ->sum('total');

        // Productos con stock bajo
        $stockBajo = Producto::where('activo', true)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->count();

        $totalesHoy = app(CajaService::class)->totalesPorFecha(today());
        $gastosHoy = (float) Gasto::whereDate('fecha', today())->sum('monto');
        $valorizacion = app(InventarioService::class)->valorizacionInventario();

        return [
            Stat::make('Ventas de Hoy', '$ '.number_format($ventasHoy, 0, ',', '.'))
                ->description('Total vendido hoy')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Ventas del Mes', '$ '.number_format($ventasMes, 0, ',', '.'))
                ->description(now()->translatedFormat('F Y'))
                ->color('info')
                ->icon('heroicon-o-calendar'),

            Stat::make('Productos Stock Bajo', $stockBajo)
                ->description('Productos por debajo del mínimo')
                ->descriptionIcon($stockBajo > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($stockBajo > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle')
                ->chart($stockBajo > 0 ? [2, 5, $stockBajo] : [1, 1, 1]),

            Stat::make('Efectivo Hoy', '$ '.number_format((float) $totalesHoy['efectivo'], 0, ',', '.'))
                ->description('Ventas en efectivo')
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Nequi Hoy', '$ '.number_format((float) $totalesHoy['nequi'], 0, ',', '.'))
                ->description('Ventas por Nequi')
                ->color('warning')
                ->icon('heroicon-o-device-phone-mobile'),

            Stat::make('Transferencias Hoy', '$ '.number_format((float) $totalesHoy['transferencias'], 0, ',', '.'))
                ->description('Transferencias bancarias')
                ->color('warning')
                ->icon('heroicon-o-device-phone-mobile'),

            Stat::make('Tarjetas Hoy', '$ '.number_format((float) $totalesHoy['tarjetas'], 0, ',', '.'))
                ->description('Ventas con tarjeta')
                ->color('info')
                ->icon('heroicon-o-credit-card'),

            Stat::make('Gastos Hoy', '$ '.number_format($gastosHoy, 0, ',', '.'))
                ->description('Egresos materia prima/servicios')
                ->color($gastosHoy > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-arrow-trending-down'),

            Stat::make('Valorización Inventario', '$ '.number_format((float) ($valorizacion['total_venta'] ?? 0), 0, ',', '.'))
                ->description(($valorizacion['count'] ?? $valorizacion['productos'] ?? 0).' productos - costo $ '.number_format((float) ($valorizacion['total_costo'] ?? 0), 0, ',', '.'))
                ->color('success')
                ->icon('heroicon-o-cube'),
        ];
    }
}
