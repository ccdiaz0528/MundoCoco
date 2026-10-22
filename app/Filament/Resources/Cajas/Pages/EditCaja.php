<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Models\Caja;
use App\Services\CajaService;
use Carbon\Carbon;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditCaja extends EditRecord
{
    protected static string $resource = CajaResource::class;

    /**
     * La caja solo se edita por el servicio: fecha, saldo inicial y
     * observaciones. Los totales los calcula el sistema (nunca se aceptan
     * del formulario) y el teórico se recalcula con el saldo inicial nuevo.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $caja = Caja::query()->lockForUpdate()->findOrFail($record->getKey());

            if ($caja->estado !== 'abierta') {
                throw ValidationException::withMessages(['caja' => 'La caja ya fue cerrada y no se puede editar.']);
            }

            validator([
                'fecha' => $data['fecha'] ?? null,
                'saldo_inicial' => $data['saldo_inicial'] ?? null,
            ], [
                'fecha' => ['required', 'date'],
                'saldo_inicial' => ['required', 'numeric', 'min:0'],
            ])->validate();

            $fecha = Carbon::parse($data['fecha'])->startOfDay();

            if (Caja::query()->whereDate('fecha', $fecha)->whereKeyNot($caja->getKey())->exists()) {
                throw ValidationException::withMessages(['fecha' => 'Ya existe otra caja para esa fecha.']);
            }

            $caja->fill([
                'fecha' => $fecha->toDateString(),
                'saldo_inicial' => $data['saldo_inicial'],
                'observaciones' => $data['observaciones'] ?? null,
            ])->saveQuietly();

            app(CajaService::class)->recalcularCajaAbierta($fecha);

            // La página sigue usando la instancia recibida: se sincroniza con
            // la fila bloqueada y con los totales recalculados.
            return $record->setRawAttributes($caja->getAttributes())->refresh();
        }, 3);
    }
}
