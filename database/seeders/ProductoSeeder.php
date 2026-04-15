<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $helados      = Categoria::where('nombre', 'Helados')->first()->id;
        $postres      = Categoria::where('nombre', 'Postres')->first()->id;
        $bebidas      = Categoria::where('nombre', 'Bebidas')->first()->id;
        $ingredientes = Categoria::where('nombre', 'Ingredientes')->first()->id;

        $productos = [
            // Helados
            ['categoria_id' => $helados, 'nombre' => 'Helado de Vainilla',    'precio_venta' => 4500,  'precio_costo' => 2000, 'stock_actual' => 30, 'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado de Chocolate',   'precio_venta' => 4500,  'precio_costo' => 2000, 'stock_actual' => 25, 'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado de Fresa',       'precio_venta' => 4500,  'precio_costo' => 2000, 'stock_actual' => 3,  'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Paleta de Coco',        'precio_venta' => 3000,  'precio_costo' => 1200, 'stock_actual' => 20, 'stock_minimo' => 8],

            // Postres
            ['categoria_id' => $postres, 'nombre' => 'Brownie con Helado',    'precio_venta' => 8000,  'precio_costo' => 3500, 'stock_actual' => 15, 'stock_minimo' => 3],
            ['categoria_id' => $postres, 'nombre' => 'Crepe de Nutella',      'precio_venta' => 7000,  'precio_costo' => 3000, 'stock_actual' => 2,  'stock_minimo' => 5],
            ['categoria_id' => $postres, 'nombre' => 'Waffle con Fresas',     'precio_venta' => 9000,  'precio_costo' => 4000, 'stock_actual' => 10, 'stock_minimo' => 3],

            // Bebidas
            ['categoria_id' => $bebidas, 'nombre' => 'Malteada de Vainilla',  'precio_venta' => 7000,  'precio_costo' => 2500, 'stock_actual' => 20, 'stock_minimo' => 5],
            ['categoria_id' => $bebidas, 'nombre' => 'Jugo de Mora',          'precio_venta' => 4000,  'precio_costo' => 1500, 'stock_actual' => 4,  'stock_minimo' => 5],
            ['categoria_id' => $bebidas, 'nombre' => 'Limonada de Coco',      'precio_venta' => 5000,  'precio_costo' => 2000, 'stock_actual' => 18, 'stock_minimo' => 5],

            // Ingredientes
            ['categoria_id' => $ingredientes, 'nombre' => 'Leche Condensada', 'precio_venta' => 3500,  'precio_costo' => 2200, 'stock_actual' => 12, 'stock_minimo' => 4],
            ['categoria_id' => $ingredientes, 'nombre' => 'Crema de Leche',   'precio_venta' => 3000,  'precio_costo' => 1800, 'stock_actual' => 3,  'stock_minimo' => 5],
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }
    }
}
