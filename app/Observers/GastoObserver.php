<?php

namespace App\Observers;

use App\Models\Gasto;
use App\Services\CajaService;
use Illuminate\Support\Carbon;

class GastoObserver
{
    private ?Carbon $fechaOriginal = null;

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
        $this->fechaOriginal = Carbon::parse($gasto->getOriginal('fecha'));
    }

    public function updated(Gasto $gasto): void
    {
        $svc = app(CajaService::class);
        $svc->recalcularCajaAbierta($this->fechaOriginal);
        $svc->recalcularCajaAbierta($gasto->fecha);
        $this->fechaOriginal = null;
    }

    public function deleted(Gasto $gasto): void
    {
        app(CajaService::class)->recalcularCajaAbierta($gasto->fecha);
    }
}
