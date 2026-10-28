<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use App\Support\AuditoriaLegible;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
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
                    ->formatStateUsing(fn (?string $state): string => AuditoriaLegible::accion($state))
                    ->color(fn (?string $state): string => AuditoriaLegible::color($state))
                    ->searchable(),
                TextColumn::make('modelo_type')
                    ->label('Registro')
                    ->state(fn (AuditLog $record): string => AuditoriaLegible::registro($record))
                    ->wrap(),
                TextColumn::make('resumen')
                    ->label('Detalle')
                    ->state(fn (AuditLog $record): string => AuditoriaLegible::resumen($record))
                    ->wrap()
                    ->limit(140),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('accion')
                    ->label('Acción')
                    ->options(fn (): array => AuditLog::query()
                        ->distinct()
                        ->orderBy('accion')
                        ->pluck('accion', 'accion')
                        ->map(fn (string $accion): string => AuditoriaLegible::accion($accion))
                        ->all()),
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
            ->recordActions([
                // RNF04: el detalle completo (a veces largo) se lee en un modal
                // en vez de quedar cortado en la celda.
                Action::make('verDetalle')
                    ->label('Ver detalle')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading(fn (AuditLog $record): string => AuditoriaLegible::accion($record->accion).' · '.AuditoriaLegible::registro($record))
                    ->modalWidth(Width::TwoExtraLarge)
                    ->modalContent(fn (AuditLog $record) => view('filament.audit-detalle', ['log' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->recordAction('verDetalle')
            ->defaultSort('created_at', 'desc');
    }
}
