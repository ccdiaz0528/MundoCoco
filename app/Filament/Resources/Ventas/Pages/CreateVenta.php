<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use App\Models\Venta;
use App\Services\VentaService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    /**
     * Toda la venta (precios, stock, movimientos, caja y auditoría) se
     * registra en VentaService::crear, dentro de una sola transacción.
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(VentaService::class)->crear($data, array_values($data['detalles'] ?? []));
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('No se pudo registrar la venta')
                ->body(implode(' ', $exception->validator->errors()->all()))
                ->send();

            throw (new Halt)->rollBackDatabaseTransaction();
        }
    }

    protected function afterCreate(): void
    {
        /** @var Venta $venta */
        $venta = $this->record;
        $cantidades = $venta->detalles->groupBy('producto_id')->map->sum('cantidad')->all();

        // RF04: alertar si la venta dejó stock por debajo del mínimo.
        $bajoMinimo = app(VentaService::class)->productosBajoMinimo($cantidades);
        if ($bajoMinimo->isNotEmpty()) {
            Notification::make()
                ->warning()
                ->title('Stock bajo el mínimo')
                ->body('Quedaron bajo el mínimo: '.$bajoMinimo->map(fn ($p) => "{$p->nombre} ({$p->stock_actual} uds)")->join(', '))
                ->send();
        }
    }
}
