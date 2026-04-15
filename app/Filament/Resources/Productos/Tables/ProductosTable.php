<?php

namespace App\Filament\Resources\Productos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('categoria.nombre')  // ✅ así accede a la relación
                    ->label('Categoría')
                    ->sortable(),
                TextColumn::make('precio_venta')
                    ->label('Precio')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('stock_actual')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn ($record) => $record->stockBajo() ? 'danger' : 'success'),
                TextColumn::make('stock_minimo')
                    ->label('Mínimo'),
                IconColumn::make('activo')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('categoria')
                    ->relationship('categoria', 'nombre'),
            ])
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
