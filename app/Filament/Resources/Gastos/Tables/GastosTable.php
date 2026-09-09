<?php

namespace App\Filament\Resources\Gastos\Tables;

use App\Models\Caja;
use App\Models\Gasto;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GastosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('categoria')
                    ->label('Categoría')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'materia_prima' => 'warning',
                        'servicios' => 'info',
                        'transporte' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('monto')
                    ->label('Monto')
                    ->money('COP')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('user.name')
                    ->label('Registrado por')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Registrado el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('categoria')
                    ->label('Categoría')
                    ->options(Gasto::CATEGORIAS),
            ])
            ->defaultSort('fecha', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn ($record) => ! Caja::whereDate('fecha', $record->fecha)->where('estado', 'cerrada')->exists()),
            ]);
    }
}
