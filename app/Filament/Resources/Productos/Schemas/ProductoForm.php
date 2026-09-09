<?php

namespace App\Filament\Resources\Productos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'nombre')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('codigo')
                    ->label('Código (RF01)')
                    ->helperText('Único en el catálogo. Se genera automáticamente si se deja vacío.')
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(150),
                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->nullable()
                    ->rows(2),
                TextInput::make('precio_venta')
                    ->label('Precio de Venta')
                    ->numeric()
                    ->prefix('$')
                    ->minValue(2000)
                    ->maxValue(95000)
                    ->helperText('RF01: entre $2.000 y $95.000.')
                    ->required(),
                TextInput::make('precio_costo')
                    ->label('Precio de Costo')
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0)
                    ->nullable(),
                TextInput::make('stock_actual')
                    ->label('Stock Actual')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                TextInput::make('stock_minimo')
                    ->label('Stock Mínimo (Alerta)')
                    ->numeric()
                    ->minValue(0)
                    ->default(5)
                    ->required(),
                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
