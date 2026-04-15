<?php

namespace App\Observers;

use App\Models\Venta;
use App\Models\Caja;

class VentaObserver
{
    public function created(Venta $venta): void
    {
        $this->recalcularCaja($venta);
    }

    public function updated(Venta $venta): void
    {
        $this->recalcularCaja($venta);
    }

    public function deleted(Venta $venta): void
    {
        $this->recalcularCaja($venta);
    }

    private function recalcularCaja(Venta $venta): void
    {
        // Busca la caja abierta del mismo día
        $caja = Caja::whereDate('fecha', today())
            ->where('estado', 'abierta')
            ->first();

        if (!$caja) return;

        // Obtiene las ventas del día agrupadas por método de pago
        $ventas = Venta::whereDate('created_at', today());

        $efectivo       = (clone $ventas)->whereHas('metodoPago', fn($q) => $q->where('nombre', 'Efectivo'))->sum('total');
        $transferencias = (clone $ventas)->whereHas('metodoPago', fn($q) => $q->where('nombre', 'Transferencia'))->sum('total');
        $tarjetas       = (clone $ventas)->whereHas('metodoPago', fn($q) => $q->where('nombre', 'Tarjeta'))->sum('total');
        $totalVentas    = (clone $ventas)->sum('total');

        $caja->saveQuietly([
            // saveQuietly evita disparar de nuevo los observers
        ]);

        $caja->total_efectivo        = $efectivo;
        $caja->total_transferencias  = $transferencias;
        $caja->total_tarjetas        = $tarjetas;
        $caja->total_ventas          = $totalVentas;
        $caja->saldo_real            = $caja->saldo_inicial + $totalVentas;
        $caja->diferencia            = $caja->saldo_real - ($caja->saldo_inicial + $totalVentas);
        $caja->saveQuietly();
    }
}
