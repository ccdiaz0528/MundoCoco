<?php

namespace App\Observers;

use App\Models\Venta;
use App\Services\CajaService;
use Illuminate\Support\Carbon;

class VentaObserver
{
    /**
     * Fechas originales por venta. Estático porque Laravel resuelve una
     * instancia distinta del observer por cada evento (Class@event).
     *
     * @var array<int, Carbon>
     */
    private static array $fechasOriginales = [];

    public function created(Venta $venta): void
    {
        app(CajaService::class)->recalcularCajaAbierta($venta->fecha_venta);
    }

    public function updating(Venta $venta): void
    {
        self::$fechasOriginales[$venta->id] = Carbon::parse($venta->getOriginal('fecha_venta'));
    }

    public function updated(Venta $venta): void
    {
        $cajaService = app(CajaService::class);
        $cajaService->recalcularCajaAbierta(self::$fechasOriginales[$venta->id] ?? $venta->fecha_venta);
        $cajaService->recalcularCajaAbierta($venta->fecha_venta);
        unset(self::$fechasOriginales[$venta->id]);
    }

    public function deleted(Venta $venta): void
    {
        app(CajaService::class)->recalcularCajaAbierta($venta->fecha_venta);
    }
}
