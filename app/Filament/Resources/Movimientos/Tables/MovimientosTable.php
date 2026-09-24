<?php

namespace App\Filament\Resources\Movimientos\Tables;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MovimientosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'inicial' => 'Inicial',
                        'compra' => 'Compra',
                        'devolucion' => 'Devolución',
                        'ajuste_positivo' => 'Ajuste positivo',
                        'venta' => 'Venta',
                        'ajuste_negativo' => 'Ajuste negativo',
                        'merma' => 'Merma',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'inicial' => 'gray',
                        'compra' => 'success',
                        'devolucion' => 'info',
                        'ajuste_positivo' => 'success',
                        'venta' => 'warning',
                        'ajuste_negativo' => 'danger',
                        'merma' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->sortable(),
                TextColumn::make('stock_anterior')
                    ->label('Anterior')
                    ->toggleable(),
                TextColumn::make('stock_nuevo')
                    ->label('Nuevo')
                    ->weight('bold'),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->toggleable(),
                TextColumn::make('motivo')
                    ->label('Motivo')
                    ->limit(25)
                    ->toggleable(),
                TextColumn::make('fecha_movimiento')
                    ->label('Fecha transacción')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                // RF12: fecha y hora exacta en que se registró en el sistema.
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Tipo de movimiento')
                    ->options([
                        'inicial' => 'Inicial',
                        'compra' => 'Compra',
                        'devolucion' => 'Devolución',
                        'ajuste_positivo' => 'Ajuste +',
                        'venta' => 'Venta',
                        'ajuste_negativo' => 'Ajuste -',
                        'merma' => 'Merma',
                    ]),
                SelectFilter::make('producto_id')
                    ->relationship('producto', 'nombre')
                    ->label('Producto')
                    ->searchable()
                    ->preload(),
                // RF12: búsqueda por usuario que realizó el movimiento.
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Usuario')
                    ->searchable()
                    ->preload(),
                // RF12: búsqueda por rango de fechas.
                Filter::make('fecha_movimiento')
                    ->label('Fecha')
                    ->form([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, $fecha): Builder => $q->whereDate('fecha_movimiento', '>=', $fecha))
                        ->when($data['hasta'] ?? null, fn (Builder $q, $fecha): Builder => $q->whereDate('fecha_movimiento', '<=', $fecha))),
            ])
            ->defaultSort('fecha_movimiento', 'desc')
            ->recordActions([]);
    }
}
