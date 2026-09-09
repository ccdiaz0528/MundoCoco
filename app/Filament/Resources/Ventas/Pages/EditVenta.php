<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource; // ✅
use App\Models\VentaDetalle;
use App\Services\VentaService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditVenta extends EditRecord
{
    protected static string $resource = VentaResource::class;

    protected array $detallesAnteriores = [];

    protected ?Carbon $fechaVentaAnterior = null;

    protected function beforeFill(): void
    {
        $this->detallesAnteriores = VentaDetalle::where('venta_id', $this->record->id)
            ->get()
            ->toArray();
        $this->fechaVentaAnterior = Carbon::parse($this->record->fecha_venta);
    }

    protected function afterSave(): void
    {
        app(VentaService::class)->reconciliarEdicion(
            $this->record,
            $this->detallesAnteriores,
            $this->fechaVentaAnterior ?? $this->record->fecha_venta,
        );
    }
}
