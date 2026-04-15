<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource; // ✅
use App\Models\Producto;
use App\Models\VentaDetalle;
use Filament\Resources\Pages\EditRecord;

class EditVenta extends EditRecord
{
    protected static string $resource = VentaResource::class;

    protected array $detallesAnteriores = [];

    protected function beforeFill(): void
    {
        $this->detallesAnteriores = VentaDetalle::where('venta_id', $this->record->id)
            ->get()
            ->toArray();
    }

    protected function afterSave(): void
    {
        foreach ($this->detallesAnteriores as $detalleAnterior) {
            Producto::where('id', $detalleAnterior['producto_id'])
                ->increment('stock_actual', $detalleAnterior['cantidad']);
        }

        foreach ($this->record->detalles as $detalle) {
            Producto::where('id', $detalle->producto_id)
                ->decrement('stock_actual', $detalle->cantidad);
        }
    }
}
