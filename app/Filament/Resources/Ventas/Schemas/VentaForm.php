<?php

namespace App\Filament\Resources\Ventas\Schemas;

use App\Models\Producto;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VentaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Información de la Venta')
                    ->icon('heroicon-o-shopping-cart')
                    ->columns(2)
                    ->schema([
                        Select::make('metodo_pago_id')
                            ->label('Método de Pago')
                            ->relationship('metodoPago', 'nombre', modifyQueryUsing: fn ($query) => $query->where('activo', true))
                            ->required()
                            ->searchable()
                            ->preload(),

                        // Solo informativo: VentaService recalcula el total con precios de BD.
                        TextInput::make('total')
                            ->label('Total Venta')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly()
                            ->dehydrated(false)
                            ->default(0),

                        Textarea::make('observaciones')
                            ->nullable()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Productos de la Venta')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        // Sin ->relationship(): los detalles los persiste VentaService
                        // (CreateVenta/EditVenta), no Filament.
                        Repeater::make('detalles')
                            ->label('')
                            ->schema([

                                // Fila 1: selector de producto ancho completo
                                Select::make('producto_id')
                                    ->label('Producto')
                                    // Búsqueda en servidor: no carga todo el catálogo al abrir el formulario.
                                    ->getSearchResultsUsing(fn (string $search): array => Producto::query()
                                        ->where('activo', true)
                                        ->where(fn ($query) => $query->where('nombre', 'like', "%{$search}%")->orWhere('codigo', 'like', "%{$search}%"))
                                        ->orderBy('nombre')
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn (Producto $producto): array => [$producto->id => "{$producto->codigo} · {$producto->nombre}"])
                                        ->all())
                                    ->getOptionLabelUsing(fn ($value): ?string => ($producto = Producto::find($value)) ? "{$producto->codigo} · {$producto->nombre}" : null)
                                    ->required()
                                    ->searchable()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $producto = Producto::find($state);
                                        if ($producto) {
                                            $set('precio_unitario', $producto->precio_venta);
                                            $set('subtotal', floatval($producto->precio_venta) * floatval($get('cantidad') ?? 1));
                                        }
                                        // Recalcula el total general
                                        $items = $get('../../detalles');
                                        $total = collect($items)->sum(fn ($i) => floatval($i['subtotal'] ?? 0));
                                        $set('../../total', $total);
                                    })
                                    ->columnSpanFull(), // ✅ ocupa toda la fila

                                // Fila 2: cantidad | precio | subtotal
                                TextInput::make('cantidad')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $subtotal = floatval($state) * floatval($get('precio_unitario'));
                                        $set('subtotal', $subtotal);
                                        // Recalcula el total general
                                        $items = $get('../../detalles');
                                        $total = collect($items)->sum(fn ($i) => floatval($i['subtotal'] ?? 0));
                                        $set('../../total', $total);
                                    }),

                                TextInput::make('precio_unitario')
                                    ->label('Precio Unitario')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->dehydrated(false),

                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->dehydrated(false),
                            ])
                            ->columns(3) // ✅ fila 2 queda en 3 columnas iguales
                            ->addActionLabel('+ Agregar producto')
                            ->minItems(1)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $total = collect($state)->sum(fn ($i) => floatval($i['subtotal'] ?? 0));
                                $set('total', $total);
                            }),
                    ]),

            ]);
    }
}
