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
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(150),
                Textarea::make('descripcion')
                    ->nullable()
                    ->rows(2),
                TextInput::make('precio_venta')
                    ->label('Precio de Venta')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                TextInput::make('precio_costo')
                    ->label('Precio de Costo')
                    ->numeric()
                    ->prefix('$')
                    ->nullable(),
                TextInput::make('stock_actual')
                    ->label('Stock Actual')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('stock_minimo')
                    ->label('Stock Mínimo (Alerta)')
                    ->numeric()
                    ->default(5)
                    ->required(),
                Toggle::make('activo')
                    ->default(true),
            ]);
    }
}
