<?php

namespace App\Filament\Resources\Ventas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                    ->badge() // ✅ lo convierte en badge
                    ->color(fn ($record) => match($record->metodoPago?->nombre) {
                        'Efectivo'      => 'success',  // 🟢 verde
                        'Transferencia' => 'info',     // 🔵 azul
                        'Tarjeta'       => 'warning',  // 🟠 naranja
                        default         => 'gray',
                    }),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('COP')
                    ->sortable(),
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
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fecha_venta', 'desc'); // las más recientes primero
    }
}
