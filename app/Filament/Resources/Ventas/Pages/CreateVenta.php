<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use App\Models\Producto;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    protected function beforeCreate(): void
    {
        $detalles = $this->data['detalles'] ?? [];

        foreach ($detalles as $detalle) {
            $producto = Producto::find($detalle['producto_id']);

            if (!$producto) continue;

            if ($detalle['cantidad'] > $producto->stock_actual) {
                // ✅ Muestra notificación de error y cancela la venta
                Notification::make()
                    ->title('Stock insuficiente')
                    ->body("El producto \"{$producto->nombre}\" solo tiene {$producto->stock_actual} unidades disponibles.")
                    ->danger()
                    ->persistent()
                    ->send();

                $this->halt(); // ✅ cancela el guardado
            }
        }
    }

    protected function afterCreate(): void
    {
        foreach ($this->record->detalles as $detalle) {
            Producto::where('id', $detalle->producto_id)
                ->decrement('stock_actual', $detalle->cantidad);
        }
    }
}
