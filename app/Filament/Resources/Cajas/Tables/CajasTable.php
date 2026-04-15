<?php

namespace App\Filament\Resources\Cajas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
            ])
            ->defaultSort('fecha', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
