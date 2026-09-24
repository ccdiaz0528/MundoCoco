<?php

namespace App\Filament\Resources\Ventas\Tables;

use App\Models\Venta;
use App\Services\VentaService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class VentasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('# Venta')
                    ->sortable(),
                TextColumn::make('metodoPago.nombre')
                    ->label('Método de Pago')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => match ($record->metodoPago?->nombre) {
                        'Efectivo' => 'success',
                        'Nequi' => 'primary',
                        'Transferencia' => 'info',
                        'Tarjeta' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Venta $record): string => $record->anulada() ? 'Anulada' : 'Vigente')
                    ->color(fn (string $state): string => $state === 'Anulada' ? 'danger' : 'success')
                    ->tooltip(fn (Venta $record): ?string => $record->motivo_anulacion),
                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(40),
                TextColumn::make('fecha_venta')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('metodoPago')
                    ->relationship('metodoPago', 'nombre')
                    ->label('Método de Pago'),
                TernaryFilter::make('anulada')
                    ->label('Anuladas')
                    ->placeholder('Todas')
                    ->trueLabel('Solo anuladas')
                    ->falseLabel('Solo vigentes')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('anulada_at'),
                        false: fn (Builder $query) => $query->whereNull('anulada_at'),
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (Venta $record): bool => auth()->user()?->can('update', $record) ?? false),
                // Devolución total: repone stock, sale de caja y reportes, queda auditada.
                Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (Venta $record): bool => auth()->user()?->can('anular', $record) ?? false)
                    ->schema([
                        Textarea::make('motivo_anulacion')
                            ->label('Motivo de la anulación / devolución')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('El stock vendido se devuelve al inventario y la venta deja de contar en caja y reportes. No se puede deshacer.')
                    ->action(function (Venta $record, array $data): void {
                        try {
                            app(VentaService::class)->anular($record, $data['motivo_anulacion']);
                            Notification::make()->success()->title('Venta anulada')->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title('No se pudo anular la venta')
                                ->body(implode(' ', $exception->validator->errors()->all()))->send();
                        }
                    }),
            ])
            ->defaultSort('fecha_venta', 'desc'); // las más recientes primero
    }
}
