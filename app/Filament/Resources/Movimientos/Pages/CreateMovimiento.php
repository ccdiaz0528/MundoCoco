<?php

namespace App\Filament\Resources\Movimientos\Pages;

use App\Filament\Resources\Movimientos\MovimientoInventarioResource;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\InventarioService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateMovimiento extends CreateRecord
{
    protected static string $resource = MovimientoInventarioResource::class;

    protected function handleRecordCreation(array $data): MovimientoInventario
    {
        $producto = Producto::findOrFail($data['producto_id']);
        $servicio = app(InventarioService::class);
        $tipo = $data['tipo'];
        $cantidad = (int) $data['cantidad'];

        return match ($tipo) {
            MovimientoInventario::TIPO_INICIAL => $servicio->registrarInicial($producto, $cantidad, $data['observaciones'] ?? null),
            MovimientoInventario::TIPO_COMPRA,
            MovimientoInventario::TIPO_DEVOLUCION,
            MovimientoInventario::TIPO_AJUSTE_POSITIVO => $servicio->adicionarStock($producto, $cantidad, $tipo, $data['motivo'] ?? null, $data['observaciones'] ?? null),
            MovimientoInventario::TIPO_AJUSTE_NEGATIVO,
            MovimientoInventario::TIPO_MERMA => $servicio->retirarStock($producto, $cantidad, $tipo, $data['motivo'] ?? null, $data['observaciones'] ?? null),
            MovimientoInventario::TIPO_VENTA => throw ValidationException::withMessages(['tipo' => 'Las ventas se registran automáticamente al vender, no manualmente.']),
            default => throw ValidationException::withMessages(['tipo' => 'Tipo no soportado.']),
        };
    }
}
