<?php

namespace App\Filament\Resources\Categorias\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoriaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(150),
                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->rows(2)
                    ->columnSpanFull(),
                Toggle::make('activo')
                    ->label('Activa')
                    ->required(),
            ]);
    }
}
