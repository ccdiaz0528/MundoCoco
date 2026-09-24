<?php

namespace App\Filament\Resources\Cajas\Schemas;

use App\Services\CajaService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if ($state) {
                                    self::calcularTotales($state, $set);
                                }
                            }),

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
                                if (! $fecha) {
                                    return;
                                }
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

                        TextInput::make('total_nequi')
                            ->label('Total Nequi')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly()
                            ->hint('Se calcula automáticamente'),

                        TextInput::make('total_transferencias')
                            ->label('Total Transferencias bancarias')
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
                            ->label('Total General Ventas')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly()
                            ->hint('Se calcula automáticamente'),

                        TextInput::make('total_gastos')
                            ->label('Total Gastos del Día')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly()
                            ->hint('Materia prima, servicios, etc.')
                            ->hintColor('danger'),

                        TextInput::make('saldo_teorico')
                            ->label('Saldo Teórico (Base + Ventas - Gastos)')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly()
                            ->hint('Esperado en caja'),

                        Textarea::make('observaciones')
                            ->nullable()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function calcularTotales(string $fecha, callable $set): void
    {
        $totales = app(CajaService::class)->totalesPorFecha($fecha);

        $set('total_efectivo', $totales['efectivo']);
        $set('total_nequi', $totales['nequi']);
        $set('total_transferencias', $totales['transferencias']);
        $set('total_tarjetas', $totales['tarjetas']);
        $set('total_ventas', $totales['total']);
        $set('total_gastos', $totales['gastos']);
        // Saldo teórico provisional (sin saldo_inicial); se calcula al abrir/cerrar con saldo inicial
        $set('saldo_teorico', (float) $totales['total'] - (float) $totales['gastos']);
    }
}
