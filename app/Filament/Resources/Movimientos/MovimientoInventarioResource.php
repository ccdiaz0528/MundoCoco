<?php

namespace App\Filament\Resources\Movimientos;

use App\Filament\Resources\Movimientos\Pages\CreateMovimiento;
use App\Filament\Resources\Movimientos\Pages\ListMovimientos;
use App\Filament\Resources\Movimientos\Schemas\MovimientoInventarioForm;
use App\Filament\Resources\Movimientos\Tables\MovimientosTable;
use App\Models\MovimientoInventario;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MovimientoInventarioResource extends Resource
{
    protected static ?string $model = MovimientoInventario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Movimientos';

    protected static UnitEnum|string|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'Movimiento';

    protected static ?string $pluralModelLabel = 'Movimientos de Inventario';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['producto', 'user']);
    }

    public static function form(Schema $schema): Schema
    {
        return MovimientoInventarioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MovimientosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMovimientos::route('/'),
            'create' => CreateMovimiento::route('/create'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
