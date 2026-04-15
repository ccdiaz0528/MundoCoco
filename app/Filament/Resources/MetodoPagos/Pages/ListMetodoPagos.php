<?php

namespace App\Filament\Resources\MetodoPagos\Pages;

use App\Filament\Resources\MetodoPagos\MetodoPagoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMetodoPagos extends ListRecords
{
    protected static string $resource = MetodoPagoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
