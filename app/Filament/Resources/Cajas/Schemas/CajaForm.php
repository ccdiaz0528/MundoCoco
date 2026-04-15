<?php

namespace App\Filament\Resources\Cajas\Schemas;

use App\Models\MetodoPago;
use App\Models\Venta;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CajaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Apertura de Caja')
                    ->icon('heroicon-o-banknotes')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('fecha')
                            ->label('Fecha')
                            ->required()
                            ->default(now())
                            ->native(false),

                        TextInput::make('saldo_inicial')
                            ->label('Saldo Inicial')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required()
                            ->hint('Dinero en caja al abrir')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $fecha = $get('fecha');
                                if (!$fecha) return;
                                self::calcularTotales($fecha, $set);
                            }),
                    ]),

                Section::make('Resumen de Ventas del Día')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_efectivo')
                            ->label('Total Efectivo')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly()
                            ->hint('Se calcula automáticamente'),

                        TextInput::make('total_transferencias')
                            ->label('Total Transferencias')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly()
                            ->hint('Se calcula automáticamente'),

                        TextInput::make('total_tarjetas')
                            ->label('Total Tarjetas')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly()
                            ->hint('Se calcula automáticamente'),

                        TextInput::make('total_ventas')
                            ->label('Total General del Día')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly()
                            ->hint('Se calcula automáticamente'),

                        Textarea::make('observaciones')
                            ->nullable()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function calcularTotales(string $fecha, callable $set): void
    {
        $ventas = Venta::whereDate('fecha_venta', $fecha)->get();

        $efectivo      = MetodoPago::where('nombre', 'Efectivo')->first();
        $transferencia = MetodoPago::where('nombre', 'Transferencia')->first();
        $tarjeta       = MetodoPago::where('nombre', 'Tarjeta')->first();

        $set('total_efectivo',       $ventas->where('metodo_pago_id', $efectivo?->id)->sum('total'));
        $set('total_transferencias', $ventas->where('metodo_pago_id', $transferencia?->id)->sum('total'));
        $set('total_tarjetas',       $ventas->where('metodo_pago_id', $tarjeta?->id)->sum('total'));
        $set('total_ventas',         $ventas->sum('total'));
    }
}
