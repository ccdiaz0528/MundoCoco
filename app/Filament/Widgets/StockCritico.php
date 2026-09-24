<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * RF07: notificación visual en el dashboard con la lista de productos con
 * stock crítico y la sugerencia de reorden.
 */
class StockCritico extends TableWidget
{
    protected static ?string $heading = 'Productos con stock crítico';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Producto::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Producto::query()
                ->with('categoria')
                ->where('activo', true)
                ->whereColumn('stock_actual', '<=', 'stock_minimo')
                ->orderBy('stock_actual'))
            ->columns([
                TextColumn::make('codigo')->label('Código'),
                TextColumn::make('nombre')->label('Producto')->weight('bold'),
                TextColumn::make('categoria.nombre')->label('Categoría'),
                TextColumn::make('stock_actual')->label('Stock')->color('danger')->weight('bold'),
                TextColumn::make('stock_minimo')->label('Mínimo'),
                TextColumn::make('sugerencia')
                    ->label('Sugerencia de reorden')
                    ->state(fn (Producto $record): string => 'Reponer '.$record->sugerenciaReorden().' uds'),
            ])
            ->emptyStateHeading('Sin alertas de stock')
            ->emptyStateDescription('Todo el inventario está por encima del mínimo.')
            ->paginated([5, 10, 25]);
    }
}
