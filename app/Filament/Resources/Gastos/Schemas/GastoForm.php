<?php

namespace App\Filament\Resources\Gastos\Schemas;

use App\Models\Gasto;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GastoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->required()
                    ->default(now())
                    ->native(false),

                TextInput::make('descripcion')
                    ->label('Descripción')
                    ->required()
                    ->maxLength(150)
                    ->placeholder('Compra materia prima, pago servicios...'),

                Select::make('categoria')
                    ->label('Categoría')
                    ->options(Gasto::CATEGORIAS)
                    ->required()
                    ->native(false)
                    ->default('otros'),

                TextInput::make('monto')
                    ->label('Monto')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->minValue(1),

                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
