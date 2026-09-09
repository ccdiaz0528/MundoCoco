<?php

namespace App\Filament\Resources\Ventas;

use App\Filament\Resources\Ventas\Pages\CreateVenta;
use App\Filament\Resources\Ventas\Pages\EditVenta;
use App\Filament\Resources\Ventas\Pages\ListVentas;
use App\Filament\Resources\Ventas\Schemas\VentaForm;
use App\Filament\Resources\Ventas\Tables\VentasTable;
use App\Models\Venta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class VentaResource extends Resource
{
    protected static ?string $model = Venta::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'Ventas';

    protected static UnitEnum|string|null $navigationGroup = 'Operación diaria';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Venta';

    protected static ?string $pluralModelLabel = 'Ventas';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['metodoPago']);
    }

    public static function form(Schema $schema): Schema
    {
        return VentaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VentasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVentas::route('/'),
            'create' => CreateVenta::route('/create'),
            'edit' => EditVenta::route('/{record}/edit'),
        ];
    }
}
