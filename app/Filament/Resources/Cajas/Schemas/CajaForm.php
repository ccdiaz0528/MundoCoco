<?php

namespace App\Filament\Resources\Cajas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CajaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('fecha')
                    ->required(),
                TextInput::make('saldo_inicial')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_efectivo')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_transferencias')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_tarjetas')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_ventas')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Textarea::make('observaciones')
                    ->columnSpanFull(),
            ]);
    }
}
