<?php

namespace App\Observers;

use App\Models\Gasto;
use App\Services\CajaService;
use Illuminate\Support\Carbon;

class GastoObserver
{
    /**
     * Fechas originales por gasto. Estático porque Laravel resuelve una
     * instancia distinta del observer por cada evento (Class@event); con
     * estado de instancia la fecha original siempre llegaba null a updated.
     *
     * @var array<int, Carbon>
     */
    private static array $fechasOriginales = [];

    public function creating(Gasto $gasto): void
    {
        $gasto->user_id ??= auth()->id();
    }

    public function created(Gasto $gasto): void
    {
        app(CajaService::class)->recalcularCajaAbierta($gasto->fecha);
    }

    public function updating(Gasto $gasto): void
    {
        self::$fechasOriginales[$gasto->id] = Carbon::parse($gasto->getOriginal('fecha'));
    }

    public function updated(Gasto $gasto): void
    {
        $svc = app(CajaService::class);
        $svc->recalcularCajaAbierta(self::$fechasOriginales[$gasto->id] ?? $gasto->fecha);
        $svc->recalcularCajaAbierta($gasto->fecha);
        unset(self::$fechasOriginales[$gasto->id]);
    }

    public function deleted(Gasto $gasto): void
    {
        app(CajaService::class)->recalcularCajaAbierta($gasto->fecha);
    }
}
