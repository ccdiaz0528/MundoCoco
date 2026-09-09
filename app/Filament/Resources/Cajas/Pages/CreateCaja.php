<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Models\Caja;
use App\Services\CajaService;
use Filament\Resources\Pages\CreateRecord;

class CreateCaja extends CreateRecord
{
    protected static string $resource = CajaResource::class;

    protected function handleRecordCreation(array $data): Caja
    {
        return app(CajaService::class)->abrir($data);
    }
}
