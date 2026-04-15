<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource; // ✅
use App\Models\Producto;
use Filament\Resources\Pages\CreateRecord;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    protected function afterCreate(): void
    {
        foreach ($this->record->detalles as $detalle) {
            Producto::where('id', $detalle->producto_id)
                ->decrement('stock_actual', $detalle->cantidad);
        }
    }
}
