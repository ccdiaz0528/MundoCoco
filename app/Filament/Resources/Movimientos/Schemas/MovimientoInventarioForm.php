<?php

namespace App\Filament\Resources\Movimientos\Schemas;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MovimientoInventarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('producto_id')
                    ->label('Producto')
                    ->relationship('producto', 'nombre')
                    ->options(Producto::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('tipo')
                    ->label('Tipo de Movimiento')
                    ->options([
                        MovimientoInventario::TIPO_INICIAL => 'Inventario Inicial',
                        MovimientoInventario::TIPO_COMPRA => 'Compra / Entrada',
                        MovimientoInventario::TIPO_DEVOLUCION => 'Devolución',
                        MovimientoInventario::TIPO_AJUSTE_POSITIVO => 'Ajuste Positivo',
                        MovimientoInventario::TIPO_AJUSTE_NEGATIVO => 'Ajuste Negativo',
                        MovimientoInventario::TIPO_MERMA => 'Merma / Pérdida',
                        MovimientoInventario::TIPO_VENTA => 'Venta (automático)',
                    ])
                    ->required()
                    ->native(false),

                TextInput::make('cantidad')
                    ->label('Cantidad')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->helperText('Debe ser positiva y mayor a cero'),

                TextInput::make('motivo')
                    ->label('Motivo')
                    ->maxLength(150)
                    ->placeholder('Compra proveedor X, Ajuste inventario, etc.'),

                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
