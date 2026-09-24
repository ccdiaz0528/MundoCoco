<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use App\Models\Venta;
use App\Services\VentaService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditVenta extends EditRecord
{
    protected static string $resource = VentaResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Venta $venta */
        $venta = $this->record;
        $data['detalles'] = $venta->detalles()
            ->get(['producto_id', 'cantidad', 'precio_unitario', 'subtotal'])
            ->toArray();

        return $data;
    }

    /**
     * La edición (stock, movimientos, caja y auditoría) se aplica en
     * VentaService::actualizar, dentro de una sola transacción.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            /** @var Venta $record */
            return app(VentaService::class)->actualizar($record, $data, array_values($data['detalles'] ?? []));
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('No se pudo editar la venta')
                ->body(implode(' ', $exception->validator->errors()->all()))
                ->send();

            throw (new Halt)->rollBackDatabaseTransaction();
        }
    }
}
