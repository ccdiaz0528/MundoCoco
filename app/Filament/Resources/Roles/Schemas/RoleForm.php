<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre del rol')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ej.: Admin, Operario, Consultor'),
                TextInput::make('guard_name')
                    ->label('Guardia')
                    ->default('web')
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Select::make('permissions')
                    ->label('Permisos')
                    ->relationship('permissions', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),
            ]);
    }
}
