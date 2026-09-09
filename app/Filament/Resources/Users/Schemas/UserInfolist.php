<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nombre'),
                TextEntry::make('email')
                    ->label('Correo electrónico'),
                TextEntry::make('roles.name')
                    ->label('Roles')
                    ->badge(),
                TextEntry::make('email_verified_at')
                    ->label('Correo verificado el')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sin verificar'),
                TextEntry::make('created_at')
                    ->label('Creado el')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
            ]);
    }
}
