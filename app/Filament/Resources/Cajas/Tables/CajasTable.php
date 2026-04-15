<?php

namespace App\Filament\Resources\Cajas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
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

                TextColumn::make('total_transferencias')
                    ->label('Transferencias')
                    ->money('COP'),

                TextColumn::make('total_tarjetas')
                    ->label('Tarjetas')
                    ->money('COP'),

                TextColumn::make('total_ventas')
                    ->label('Total del Día')
                    ->money('COP')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('saldo_real')
                    ->label('Saldo Real')
                    ->money('COP')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('diferencia')
                    ->label('Diferencia')
                    ->money('COP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('fecha', 'desc')
            ->recordActions([
                EditAction::make(),

                Action::make('cerrarCaja')
                    ->label('Cerrar caja')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn ($record) => $record->estado === 'abierta')
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
                        $record->saldo_real = $data['saldo_real'];
                        $record->diferencia = $data['saldo_real'] - ($record->saldo_inicial + $record->total_ventas);
                        $record->estado = 'cerrada';
                        $record->fecha_cierre = now();
                        $record->observaciones_cierre = $data['observaciones_cierre'] ?? null;
                        $record->save();
                    })
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
