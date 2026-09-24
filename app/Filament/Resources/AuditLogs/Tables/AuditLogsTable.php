<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('Sistema'),
                TextColumn::make('accion')
                    ->label('Acción')
                    ->badge()
                    ->searchable(),
                TextColumn::make('modelo')
                    ->label('Registro')
                    ->state(fn (AuditLog $record): ?string => $record->modelo_type ? class_basename($record->modelo_type).' #'.$record->modelo_id : null),
                TextColumn::make('cambios')
                    ->label('Detalle')
                    ->state(fn (AuditLog $record): string => json_encode($record->cambios, JSON_UNESCAPED_UNICODE) ?: '')
                    ->limit(60)
                    ->tooltip(fn (AuditLog $record): string => json_encode($record->cambios, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: ''),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('accion')
                    ->label('Acción')
                    ->options(fn (): array => AuditLog::query()->distinct()->orderBy('accion')->pluck('accion', 'accion')->all()),
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Usuario')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->label('Fecha')
                    ->form([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, $fecha): Builder => $q->whereDate('created_at', '>=', $fecha))
                        ->when($data['hasta'] ?? null, fn (Builder $q, $fecha): Builder => $q->whereDate('created_at', '<=', $fecha))),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([]);
    }
}
