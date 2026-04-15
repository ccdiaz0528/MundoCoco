<?php

namespace App\Filament\Resources\Cajas;

use App\Filament\Resources\Cajas\Pages\CreateCaja;
use App\Filament\Resources\Cajas\Pages\EditCaja;
use App\Filament\Resources\Cajas\Pages\ListCajas;
use App\Filament\Resources\Cajas\Schemas\CajaForm;
use App\Filament\Resources\Cajas\Tables\CajasTable;
use App\Models\Caja;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CajaResource extends Resource
{
    protected static ?string $model = Caja::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CajaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CajasTable::configure($table);
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
            'index' => ListCajas::route('/'),
            'create' => CreateCaja::route('/create'),
            'edit' => EditCaja::route('/{record}/edit'),
        ];
    }
}
