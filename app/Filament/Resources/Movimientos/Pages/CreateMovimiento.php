<?php

namespace App\Filament\Resources\Movimientos\Pages;

use App\Filament\Resources\Movimientos\MovimientoInventarioResource;
use App\Models\MovimientoInventario;
use App\Services\InventarioService;
use Filament\Resources\Pages\CreateRecord;

class CreateMovimiento extends CreateRecord
{
    protected static string $resource = MovimientoInventarioResource::class;

    /** RF02/RF03: todas las líneas se registran en una sola transacción (InventarioService). */
    protected function handleRecordCreation(array $data): MovimientoInventario
    {
        return app(InventarioService::class)->registrarLote(
            $data['tipo'],
            array_values($data['lineas'] ?? []),
            $data['motivo'] ?? null,
            $data['observaciones'] ?? null,
            $data['fecha_movimiento'] ?? null,
        )->first();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
