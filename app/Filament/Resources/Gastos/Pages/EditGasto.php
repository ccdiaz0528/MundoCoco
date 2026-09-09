<?php

namespace App\Filament\Resources\Gastos\Pages;

use App\Filament\Resources\Gastos\GastoResource;
use App\Services\CajaService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditGasto extends EditRecord
{
    protected static string $resource = GastoResource::class;

    private ?Carbon $fechaOriginal = null;

    protected function beforeFill(): void
    {
        $this->fechaOriginal = Carbon::parse($this->record->fecha);
    }

    protected function afterSave(): void
    {
        $svc = app(CajaService::class);
        $svc->recalcularCajaAbierta($this->fechaOriginal);
        $svc->recalcularCajaAbierta($this->record->fecha);
    }
}
