<?php

namespace App\Filament\Widgets;

use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Venta;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Ventas de hoy
        $ventasHoy = Venta::whereDate('fecha_venta', today())->sum('total');

        // Ventas del mes actual
        $ventasMes = Venta::whereMonth('fecha_venta', now()->month)
            ->whereYear('fecha_venta', now()->year)
            ->sum('total');

        // Productos con stock bajo
        $stockBajo = Producto::where('activo', true)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->count();

        // Totales por método de pago hoy
        $efectivo      = MetodoPago::where('nombre', 'Efectivo')->first();
        $transferencia = MetodoPago::where('nombre', 'Transferencia')->first();
        $tarjeta       = MetodoPago::where('nombre', 'Tarjeta')->first();

        $totalEfectivo = Venta::whereDate('fecha_venta', today())
            ->where('metodo_pago_id', $efectivo?->id)
            ->sum('total');

        $totalTransferencias = Venta::whereDate('fecha_venta', today())
            ->where('metodo_pago_id', $transferencia?->id)
            ->sum('total');

        $totalTarjetas = Venta::whereDate('fecha_venta', today())
            ->where('metodo_pago_id', $tarjeta?->id)
            ->sum('total');

        return [
            Stat::make('Ventas de Hoy', '$ ' . number_format($ventasHoy, 0, ',', '.'))
                ->description('Total vendido hoy')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Ventas del Mes', '$ ' . number_format($ventasMes, 0, ',', '.'))
                ->description(now()->translatedFormat('F Y'))
                ->color('info')
                ->icon('heroicon-o-calendar'),

            Stat::make('Productos Stock Bajo', $stockBajo)
                ->description('Productos por debajo del mínimo')
                ->color($stockBajo > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make('Efectivo Hoy', '$ ' . number_format($totalEfectivo, 0, ',', '.'))
                ->description('Ventas en efectivo')
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Transferencias Hoy', '$ ' . number_format($totalTransferencias, 0, ',', '.'))
                ->description('Ventas por transferencia')
                ->color('warning')
                ->icon('heroicon-o-device-phone-mobile'),

            Stat::make('Tarjetas Hoy', '$ ' . number_format($totalTarjetas, 0, ',', '.'))
                ->description('Ventas con tarjeta')
                ->color('info')
                ->icon('heroicon-o-credit-card'),
        ];
    }
}
