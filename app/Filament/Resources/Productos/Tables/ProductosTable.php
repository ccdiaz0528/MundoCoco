<?php

namespace App\Filament\Resources\Productos\Tables;

use App\Models\Producto;
use App\Services\InventarioService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
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
                // RF05: Stock_Total = Inicial + Entradas - Salidas desde los movimientos.
                TextColumn::make('stock_calculado')
                    ->label('Stock calculado')
                    ->state(fn (Producto $record): int => app(InventarioService::class)->calcularStockTotal($record))
                    ->color(fn (Producto $record, int $state): string => $state === (int) $record->stock_actual ? 'gray' : 'danger')
                    ->tooltip('Inicial + Entradas - Salidas. En rojo si no coincide con el stock registrado.')
                    ->toggleable(),
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
                // RF01: eliminados lógicamente (restaurables).
                TrashedFilter::make()->label('Eliminados'),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
                DeleteAction::make()->label('Eliminar'),
                RestoreAction::make()->label('Restaurar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Eliminar seleccionados'),
                    RestoreBulkAction::make()->label('Restaurar seleccionados'),
                ]),
            ]);
    }
}
