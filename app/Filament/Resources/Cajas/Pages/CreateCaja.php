<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Models\MetodoPago;
use App\Models\Venta;
use Filament\Resources\Pages\CreateRecord;

class CreateCaja extends CreateRecord
{
    protected static string $resource = CajaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $fecha = $data['fecha'];

        // Trae todas las ventas del día agrupadas por método de pago
        $ventas = Venta::whereDate('fecha_venta', $fecha)->get();

        // Busca los IDs de cada método de pago por nombre
        $efectivo      = MetodoPago::where('nombre', 'Efectivo')->first();
        $transferencia = MetodoPago::where('nombre', 'Transferencia')->first();
        $tarjeta       = MetodoPago::where('nombre', 'Tarjeta')->first();

        $data['total_efectivo'] = $ventas
            ->where('metodo_pago_id', $efectivo?->id)
            ->sum('total');

        $data['total_transferencias'] = $ventas
            ->where('metodo_pago_id', $transferencia?->id)
            ->sum('total');

        $data['total_tarjetas'] = $ventas
            ->where('metodo_pago_id', $tarjeta?->id)
            ->sum('total');

        $data['total_ventas'] = $ventas->sum('total');

        return $data;
    }
}
