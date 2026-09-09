<?php

namespace App\Filament\Resources\Gastos\Pages;

use App\Filament\Resources\Gastos\GastoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGastos extends ListRecords
{
    protected static string $resource = GastoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo gasto'),
        ];
    }
}
