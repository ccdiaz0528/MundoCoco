<?php

namespace App\Filament\Resources\Gastos\Pages;

use App\Filament\Resources\Gastos\GastoResource;
use App\Models\Caja;
use App\Services\CajaService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateGasto extends CreateRecord
{
    protected static string $resource = GastoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        // Bloquear si la caja de la fecha ya está cerrada
        if (Caja::whereDate('fecha', $data['fecha'])->where('estado', 'cerrada')->exists()) {
            throw ValidationException::withMessages(['fecha' => 'No se pueden registrar gastos en una caja cerrada.']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        app(CajaService::class)->recalcularCajaAbierta($this->record->fecha);
    }
}
