<?php

namespace App\Filament\Resources\Gastos\Pages;

use App\Filament\Resources\Gastos\GastoResource;
use App\Models\Caja;
use App\Models\Gasto;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EditGasto extends EditRecord
{
    protected static string $resource = GastoResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $gasto = Gasto::query()->lockForUpdate()->findOrFail($record->getKey());
        Gate::authorize('update', $gasto);

        $fechaOriginal = $gasto->fecha->toDateString();
        $fechaNueva = $data['fecha'];
        $cajas = Caja::query()
            ->where(fn (Builder $query) => $query->whereDate('fecha', $fechaOriginal)->orWhereDate('fecha', $fechaNueva))
            ->orderBy('fecha')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($cajas->contains('estado', 'cerrada')) {
            throw ValidationException::withMessages([
                'data.fecha' => 'No se pueden editar gastos en una fecha con caja cerrada.',
            ]);
        }

        $gasto->update($data);
        $this->record = $gasto;

        return $gasto;
    }
}
