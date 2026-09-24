<?php

namespace App\Filament\Resources\Movimientos\Schemas;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MovimientoInventarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Movimiento de inventario')
                    ->columns(2)
                    ->schema([
                        Select::make('tipo')
                            ->label('Tipo de Movimiento')
                            ->options([
                                MovimientoInventario::TIPO_INICIAL => 'Inventario Inicial',
                                MovimientoInventario::TIPO_COMPRA => 'Compra / Entrada',
                                MovimientoInventario::TIPO_DEVOLUCION => 'Devolución',
                                MovimientoInventario::TIPO_AJUSTE_POSITIVO => 'Ajuste Positivo',
                                MovimientoInventario::TIPO_AJUSTE_NEGATIVO => 'Ajuste Negativo',
                                MovimientoInventario::TIPO_MERMA => 'Merma / Pérdida',
                            ])
                            ->required()
                            ->native(false),

                        // RF02 "fecha de registro" / RF03 "fecha y hora de la transacción".
                        DateTimePicker::make('fecha_movimiento')
                            ->label('Fecha y hora de la transacción')
                            ->required()
                            ->default(now())
                            ->maxDate(now()->endOfDay())
                            ->seconds(false)
                            ->native(false),

                        TextInput::make('motivo')
                            ->label('Motivo')
                            ->maxLength(150)
                            ->placeholder('Compra proveedor X, Ajuste inventario, etc.'),

                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(2),
                    ]),

                // RF03: "Producto(s) seleccionado(s)" - varios productos en un solo registro.
                Section::make('Productos')
                    ->schema([
                        Repeater::make('lineas')
                            ->label('')
                            ->schema([
                                Select::make('producto_id')
                                    ->label('Producto')
                                    ->options(fn (): array => Producto::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all())
                                    ->required()
                                    ->searchable()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                TextInput::make('cantidad')
                                    ->label('Cantidad')
                                    ->integer()
                                    ->minValue(1)
                                    ->required()
                                    ->helperText('Debe ser positiva y mayor a cero'),
                            ])
                            ->columns(2)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('+ Agregar producto'),
                    ]),
            ]);
    }
}
