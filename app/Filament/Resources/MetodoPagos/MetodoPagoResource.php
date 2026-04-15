<?php

namespace App\Filament\Resources\MetodoPagos;

use App\Filament\Resources\MetodoPagos\Pages\CreateMetodoPago;
use App\Filament\Resources\MetodoPagos\Pages\EditMetodoPago;
use App\Filament\Resources\MetodoPagos\Pages\ListMetodoPagos;
use App\Filament\Resources\MetodoPagos\Schemas\MetodoPagoForm;
use App\Filament\Resources\MetodoPagos\Tables\MetodoPagosTable;
use App\Models\MetodoPago;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MetodoPagoResource extends Resource
{
    protected static ?string $model = MetodoPago::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return MetodoPagoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MetodoPagosTable::configure($table);
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
            'index' => ListMetodoPagos::route('/'),
            'create' => CreateMetodoPago::route('/create'),
            'edit' => EditMetodoPago::route('/{record}/edit'),
        ];
    }
}
