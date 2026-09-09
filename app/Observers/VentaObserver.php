<?php

namespace App\Observers;

use App\Models\Venta;
use App\Services\CajaService;
use Illuminate\Support\Carbon;

class VentaObserver
{
    /** @var array<int, Carbon> */
    private array $fechasOriginales = [];

    public function created(Venta $venta): void
    {
        app(CajaService::class)->recalcularCajaAbierta($venta->fecha_venta);
    }

    public function updating(Venta $venta): void
    {
        $this->fechasOriginales[$venta->id] = Carbon::parse($venta->getOriginal('fecha_venta'));
    }

    public function updated(Venta $venta): void
    {
        $cajaService = app(CajaService::class);
        $cajaService->recalcularCajaAbierta($this->fechasOriginales[$venta->id] ?? $venta->fecha_venta);
        $cajaService->recalcularCajaAbierta($venta->fecha_venta);
        unset($this->fechasOriginales[$venta->id]);
    }

    public function deleted(Venta $venta): void
    {
        app(CajaService::class)->recalcularCajaAbierta($venta->fecha_venta);
    }
}
