<?php

namespace App\Filament\Resources\Movimientos\Pages;

use App\Filament\Resources\Movimientos\MovimientoInventarioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMovimientos extends ListRecords
{
    protected static string $resource = MovimientoInventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo movimiento'),
        ];
    }
}
