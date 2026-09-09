<?php

namespace App\Filament\Resources\Gastos;

use App\Filament\Resources\Gastos\Pages\CreateGasto;
use App\Filament\Resources\Gastos\Pages\EditGasto;
use App\Filament\Resources\Gastos\Pages\ListGastos;
use App\Filament\Resources\Gastos\Schemas\GastoForm;
use App\Filament\Resources\Gastos\Tables\GastosTable;
use App\Models\Gasto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GastoResource extends Resource
{
    protected static ?string $model = Gasto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Gastos';

    protected static UnitEnum|string|null $navigationGroup = 'Operación diaria';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Gasto';

    protected static ?string $pluralModelLabel = 'Gastos';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }

    public static function form(Schema $schema): Schema
    {
        return GastoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GastosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGastos::route('/'),
            'create' => CreateGasto::route('/create'),
            'edit' => EditGasto::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
