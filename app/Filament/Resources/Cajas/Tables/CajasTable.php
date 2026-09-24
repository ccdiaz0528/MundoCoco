<?php

namespace App\Filament\Resources\Cajas\Tables;

use App\Services\CajaService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CajasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'abierta' ? 'success' : 'gray'),

                TextColumn::make('saldo_inicial')
                    ->label('Saldo Inicial')
                    ->money('COP')
                    ->sortable(),

                TextColumn::make('total_efectivo')
                    ->label('Efectivo')
                    ->money('COP'),

                TextColumn::make('total_nequi')
                    ->label('Nequi')
                    ->money('COP'),

                TextColumn::make('total_transferencias')
                    ->label('Transferencias')
                    ->money('COP'),

                TextColumn::make('total_tarjetas')
                    ->label('Tarjetas')
                    ->money('COP'),

                TextColumn::make('total_ventas')
                    ->label('Total Ventas')
                    ->money('COP')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('total_gastos')
                    ->label('Gastos')
                    ->money('COP')
                    ->color('danger'),

                TextColumn::make('saldo_teorico')
                    ->label('Saldo Teórico')
                    ->money('COP')
                    ->toggleable(),

                TextColumn::make('saldo_real')
                    ->label('Saldo Real')
                    ->money('COP')
                    ->toggleable(),

                TextColumn::make('diferencia')
                    ->label('Diferencia')
                    ->money('COP')
                    ->placeholder('Pendiente de cierre')
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'gray' : ((float) $state === 0.0 ? 'success' : ((float) $state < 0 ? 'danger' : 'warning'))),
            ])
            ->defaultSort('fecha', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn ($record): bool => $record->estado === 'abierta'),

                Action::make('cerrarCaja')
                    ->label('Cerrar caja')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn ($record) => auth()->user()?->can('cerrar', $record) ?? false)
                    ->form([
                        TextInput::make('saldo_real')
                            ->label('Dinero contado')
                            ->numeric()
                            ->required()
                            ->prefix('$'),

                        Textarea::make('observaciones_cierre')
                            ->label('Observaciones del cierre')
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->action(function ($record, array $data): void {
                        app(CajaService::class)->cerrar($record, $data);
                    })
                    ->requiresConfirmation(),
            ]);
    }
}
