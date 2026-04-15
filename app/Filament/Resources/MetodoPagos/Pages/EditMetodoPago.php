<?php

namespace App\Filament\Resources\MetodoPagos\Pages;

use App\Filament\Resources\MetodoPagos\MetodoPagoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMetodoPago extends EditRecord
{
    protected static string $resource = MetodoPagoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
