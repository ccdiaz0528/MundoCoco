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
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('precio_venta')
                    ->label('Precio de venta')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('precio_costo')
                    ->label('Precio de costo')
                    ->money('COP')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('stock_actual')
                    ->label('Stock')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($record) => $record->stockBajo() ? 'danger' : 'success'),
                TextColumn::make('stock_minimo')
                    ->label('Mínimo')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('categoria')
                    ->label('Categoría')
                    ->relationship('categoria', 'nombre'),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Eliminar seleccionados'),
                ]),
            ]);
    }
}
