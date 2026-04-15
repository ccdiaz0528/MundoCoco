<?php

namespace App\Filament\Resources\Ventas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VentaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('metodo_pago_id')
                    ->label('Método de Pago')
                    ->relationship('metodoPago', 'nombre')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('total')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Textarea::make('observaciones')
                    ->nullable()
                    ->rows(2),
            ]);
    }
}
