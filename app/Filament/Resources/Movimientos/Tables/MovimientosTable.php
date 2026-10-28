<?php

namespace App\Filament\Resources\Movimientos\Tables;

use App\Filament\Resources\Ventas\VentaResource;
use App\Models\MovimientoInventario;
use App\Models\Venta;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
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
            ->recordActions([
                // RF12: el historial es inmutable, así que el detalle se consulta
                // en un modal de solo lectura, igual que en el listado de ventas.
                ViewAction::make()
                    ->label('Ver')
                    ->visible(fn (MovimientoInventario $record): bool => auth()->user()?->can('view', $record) ?? false)
                    ->modalHeading(fn (MovimientoInventario $record): string => "Movimiento #{$record->id}")
                    ->modalCancelActionLabel('Cerrar')
                    ->schema([
                        Section::make('Movimiento')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('tipo')
                                    ->label('Tipo de movimiento')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        MovimientoInventario::TIPO_INICIAL => 'Inventario inicial',
                                        MovimientoInventario::TIPO_COMPRA => 'Compra / Entrada',
                                        MovimientoInventario::TIPO_DEVOLUCION => 'Devolución',
                                        MovimientoInventario::TIPO_AJUSTE_POSITIVO => 'Ajuste positivo',
                                        MovimientoInventario::TIPO_VENTA => 'Venta',
                                        MovimientoInventario::TIPO_AJUSTE_NEGATIVO => 'Ajuste negativo',
                                        MovimientoInventario::TIPO_MERMA => 'Merma / Pérdida',
                                        default => $state,
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        MovimientoInventario::TIPO_INICIAL => 'gray',
                                        MovimientoInventario::TIPO_COMPRA => 'success',
                                        MovimientoInventario::TIPO_DEVOLUCION => 'info',
                                        MovimientoInventario::TIPO_AJUSTE_POSITIVO => 'success',
                                        MovimientoInventario::TIPO_VENTA => 'warning',
                                        MovimientoInventario::TIPO_AJUSTE_NEGATIVO => 'danger',
                                        MovimientoInventario::TIPO_MERMA => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('cantidad')
                                    ->label('Cantidad')
                                    ->suffix(fn (MovimientoInventario $record): string => $record->esEntrada() ? ' unidades (entrada)' : ' unidades (salida)'),
                                TextEntry::make('fecha_movimiento')
                                    ->label('Fecha y hora de la transacción')
                                    ->dateTime('d/m/Y H:i:s'),
                                TextEntry::make('stock_anterior')
                                    ->label('Stock anterior'),
                                TextEntry::make('stock_nuevo')
                                    ->label('Stock nuevo')
                                    ->weight('bold'),
                                TextEntry::make('created_at')
                                    ->label('Registrado en el sistema')
                                    ->dateTime('d/m/Y H:i:s'),
                            ]),
                        Section::make('Producto')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('producto.codigo')
                                    ->label('Código')
                                    ->placeholder('—'),
                                TextEntry::make('producto.nombre')
                                    ->label('Producto')
                                    ->placeholder('Producto eliminado'),
                                TextEntry::make('producto.categoria.nombre')
                                    ->label('Categoría')
                                    ->placeholder('—'),
                            ]),
                        Section::make('Registro')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Usuario')
                                    ->placeholder('—'),
                                TextEntry::make('referencia_type')
                                    ->label('Documento origen')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        Venta::class => 'Venta',
                                        null, '' => 'Sin documento asociado',
                                        default => $state,
                                    }),
                                TextEntry::make('referencia_id')
                                    ->label('N.º de documento')
                                    ->placeholder('—')
                                    ->url(fn (MovimientoInventario $record): ?string => $record->referencia_type === Venta::class && $record->referencia_id
                                        ? VentaResource::getUrl('edit', ['record' => $record->referencia_id])
                                        : null),
                                TextEntry::make('motivo')
                                    ->label('Motivo')
                                    ->placeholder('—'),
                                TextEntry::make('observaciones')
                                    ->label('Observaciones')
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
