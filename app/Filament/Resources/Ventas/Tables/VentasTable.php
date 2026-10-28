<?php

namespace App\Filament\Resources\Ventas\Tables;

use App\Models\Venta;
use App\Services\ReporteExportService;
use App\Services\VentaService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

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
                    ->badge()
                    ->color(fn ($record) => match ($record->metodoPago?->nombre) {
                        'Efectivo' => 'success',
                        'Nequi' => 'primary',
                        'Transferencia' => 'info',
                        'Tarjeta' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Venta $record): string => $record->anulada() ? 'Anulada' : 'Vigente')
                    ->color(fn (string $state): string => $state === 'Anulada' ? 'danger' : 'success')
                    ->tooltip(fn (Venta $record): ?string => $record->motivo_anulacion),
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
                TernaryFilter::make('anulada')
                    ->label('Anuladas')
                    ->placeholder('Todas')
                    ->trueLabel('Solo anuladas')
                    ->falseLabel('Solo vigentes')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('anulada_at'),
                        false: fn (Builder $query) => $query->whereNull('anulada_at'),
                    ),
                SelectFilter::make('categoria_anulacion')
                    ->label('Causa de anulación')
                    ->options(config('mundococo.motivos_anulacion')),
            ])
            ->recordActions([
                // RF04: el detalle se consulta siempre (solo lectura), incluso si la
                // venta está anulada o la caja del día está cerrada, casos en los que
                // ya no se puede editar. Antes el detalle solo existía en el formulario
                // de edición, así que al ocultarse "Editar" no quedaba forma de verlo.
                ViewAction::make()
                    ->label('Ver')
                    ->visible(fn (Venta $record): bool => auth()->user()?->can('view', $record) ?? false)
                    ->modalHeading(fn (Venta $record): string => "Venta #{$record->id}")
                    ->modalCancelActionLabel('Cerrar')
                    ->schema([
                        Section::make('Datos de la venta')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('fecha_venta')
                                    ->label('Fecha y hora')
                                    ->dateTime('d/m/Y H:i'),
                                TextEntry::make('metodoPago.nombre')
                                    ->label('Método de pago')
                                    ->badge(),
                                TextEntry::make('total')
                                    ->label('Total')
                                    ->money('COP'),
                                TextEntry::make('estado')
                                    ->label('Estado')
                                    ->state(fn (Venta $record): string => $record->anulada() ? 'Anulada' : 'Vigente')
                                    ->badge()
                                    ->color(fn (string $state): string => $state === 'Anulada' ? 'danger' : 'success'),
                                TextEntry::make('anulada_at')
                                    ->label('Anulada el')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('—'),
                                TextEntry::make('anuladaPor.name')
                                    ->label('Anulada por')
                                    ->placeholder('—'),
                                TextEntry::make('categoria_anulacion')
                                    ->label('Causa de la anulación')
                                    ->formatStateUsing(fn (?string $state): ?string => $state === null
                                        ? null
                                        : (config('mundococo.motivos_anulacion')[$state] ?? $state))
                                    ->placeholder('—'),
                                TextEntry::make('motivo_anulacion')
                                    ->label('Motivo de la anulación')
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                                TextEntry::make('observaciones')
                                    ->label('Observaciones')
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Productos de la venta')
                            ->schema([
                                RepeatableEntry::make('detalles')
                                    ->label('')
                                    ->columns(5)
                                    ->schema([
                                        TextEntry::make('producto.codigo')
                                            ->label('Código'),
                                        TextEntry::make('producto.nombre')
                                            ->label('Producto'),
                                        TextEntry::make('cantidad')
                                            ->label('Cantidad'),
                                        TextEntry::make('precio_unitario')
                                            ->label('Precio unitario')
                                            ->money('COP'),
                                        TextEntry::make('subtotal')
                                            ->label('Subtotal')
                                            ->money('COP'),
                                    ]),
                            ]),
                    ]),
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (Venta $record): bool => auth()->user()?->can('update', $record) ?? false),
                // Devolución total: repone stock, sale de caja y reportes, queda auditada.
                Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (?Venta $record): bool => $record !== null && (auth()->user()?->can('anular', $record) ?? false))
                    ->schema([
                        Select::make('categoria_anulacion')
                            ->label('Causa de la anulación')
                            ->options(config('mundococo.motivos_anulacion'))
                            ->required()
                            ->live()
                            // Dañado/vencido o consumo: la mercancía no puede volver
                            // a venderse, así que se propone descartarla.
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
                                'descartar',
                                in_array($state, ['producto_defectuoso', 'consumo_interno'], true),
                            ))
                            ->native(false),
                        Toggle::make('descartar')
                            ->label('El producto no vuelve al inventario (se descarta)')
                            ->helperText('Registra la devolución y una merma por la misma cantidad: el stock no se infla y la pérdida queda contabilizada. El dinero se devuelve igual.')
                            ->default(false),
                        Textarea::make('motivo_anulacion')
                            ->label('Motivo de la anulación / devolución')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('El stock vendido se repone al inventario (o se da de baja como merma si lo descartas) y la venta deja de contar en caja y reportes. No se puede deshacer.')
                    ->action(function (Venta $record, array $data): void {
                        try {
                            app(VentaService::class)->anular(
                                $record,
                                $data['motivo_anulacion'],
                                $data['categoria_anulacion'],
                                (bool) ($data['descartar'] ?? false),
                            );
                            Notification::make()->success()->title('Venta anulada')->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title('No se pudo anular la venta')
                                ->body(implode(' ', $exception->validator->errors()->all()))->send();
                        }
                    }),
            ])
            // Selección múltiple: Filament solo pinta las casillas cuando existe
            // una acción masiva visible, así que se usa únicamente para exportar.
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('exportar')
                        ->label('Exportar seleccionadas (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->visible(fn (): bool => auth()->user()?->can('exportar reportes') ?? false)
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => app(ReporteExportService::class)
                            ->ventasSeleccionadasCsv($records)),
                ]),
            ])
            ->defaultSort('fecha_venta', 'desc'); // las más recientes primero
    }
}
