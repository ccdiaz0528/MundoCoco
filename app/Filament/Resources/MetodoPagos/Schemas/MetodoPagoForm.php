<?php

namespace App\Filament\Resources\MetodoPagos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MetodoPagoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Ej.: Efectivo, Transferencia, Tarjeta'),
                Toggle::make('activo')
                    ->label('Activo')
                    ->required(),
            ]);
    }
}
