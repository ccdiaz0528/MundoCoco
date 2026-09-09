<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $helados = Categoria::where('nombre', 'Helados')->firstOrFail()->id;
        $bebidas = Categoria::where('nombre', 'Bebidas')->firstOrFail()->id;
        $aceites = Categoria::where('nombre', 'Aceites')->firstOrFail()->id;
        $coco = Categoria::where('nombre', 'Productos de Coco')->firstOrFail()->id;

        $productos = [
            // Helados - 14 sabores RF06
            ['categoria_id' => $helados, 'nombre' => 'Helado Arequipe',       'precio_venta' => 4500,  'precio_costo' => 2000, 'stock_actual' => 30, 'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado Bocadillo',      'precio_venta' => 4500,  'precio_costo' => 2000, 'stock_actual' => 25, 'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado Brownie',        'precio_venta' => 5000,  'precio_costo' => 2200, 'stock_actual' => 18, 'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado Café',           'precio_venta' => 4500,  'precio_costo' => 2000, 'stock_actual' => 12, 'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado Chicle',         'precio_venta' => 4500,  'precio_costo' => 2000, 'stock_actual' => 20, 'stock_minimo' => 8],
            ['categoria_id' => $helados, 'nombre' => 'Helado Chocolate',      'precio_venta' => 4500,  'precio_costo' => 2000, 'stock_actual' => 3,  'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado Fresa',          'precio_venta' => 4500,  'precio_costo' => 2000, 'stock_actual' => 22, 'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado Guanábana',      'precio_venta' => 4800,  'precio_costo' => 2100, 'stock_actual' => 15, 'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado Mangoviche',     'precio_venta' => 4800,  'precio_costo' => 2100, 'stock_actual' => 10, 'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado Mora',           'precio_venta' => 4500,  'precio_costo' => 2000, 'stock_actual' => 8,  'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado Maní',           'precio_venta' => 4700,  'precio_costo' => 2050, 'stock_actual' => 14, 'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado Maracuyá',       'precio_venta' => 4500,  'precio_costo' => 2000, 'stock_actual' => 16, 'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado Ron con Pasas',  'precio_venta' => 5000,  'precio_costo' => 2300, 'stock_actual' => 9,  'stock_minimo' => 5],
            ['categoria_id' => $helados, 'nombre' => 'Helado Salpicón',       'precio_venta' => 4700,  'precio_costo' => 2100, 'stock_actual' => 11, 'stock_minimo' => 5],

            // Bebidas RF06
            ['categoria_id' => $bebidas, 'nombre' => 'Limonada',              'precio_venta' => 4000,  'precio_costo' => 1500, 'stock_actual' => 25, 'stock_minimo' => 8],
            ['categoria_id' => $bebidas, 'nombre' => 'Mandarinada',           'precio_venta' => 4000,  'precio_costo' => 1500, 'stock_actual' => 20, 'stock_minimo' => 8],
            ['categoria_id' => $bebidas, 'nombre' => 'Cerezada',              'precio_venta' => 4500,  'precio_costo' => 1700, 'stock_actual' => 18, 'stock_minimo' => 8],
            ['categoria_id' => $bebidas, 'nombre' => 'Avena',                 'precio_venta' => 3500,  'precio_costo' => 1400, 'stock_actual' => 15, 'stock_minimo' => 5],
            ['categoria_id' => $bebidas, 'nombre' => 'Cocofee',               'precio_venta' => 5500,  'precio_costo' => 2200, 'stock_actual' => 12, 'stock_minimo' => 5],
            ['categoria_id' => $bebidas, 'nombre' => 'Cocoloco',              'precio_venta' => 6000,  'precio_costo' => 2500, 'stock_actual' => 10, 'stock_minimo' => 5],
            ['categoria_id' => $bebidas, 'nombre' => 'Cocobaileys',           'precio_venta' => 6500,  'precio_costo' => 2800, 'stock_actual' => 7,  'stock_minimo' => 4],
            ['categoria_id' => $bebidas, 'nombre' => 'Jugo de Coco',          'precio_venta' => 5000,  'precio_costo' => 2000, 'stock_actual' => 18, 'stock_minimo' => 5],
            ['categoria_id' => $bebidas, 'nombre' => 'Agua de Coco',          'precio_venta' => 3500,  'precio_costo' => 1200, 'stock_actual' => 30, 'stock_minimo' => 10],
            ['categoria_id' => $bebidas, 'nombre' => 'Limonada Natural',        'precio_venta' => 4000,  'precio_costo' => 1500, 'stock_actual' => 22, 'stock_minimo' => 8],
            ['categoria_id' => $bebidas, 'nombre' => 'Agua Natural',            'precio_venta' => 3000,  'precio_costo' => 1000, 'stock_actual' => 35, 'stock_minimo' => 10],
            ['categoria_id' => $bebidas, 'nombre' => 'Agua con Gas',            'precio_venta' => 3500,  'precio_costo' => 1300, 'stock_actual' => 28, 'stock_minimo' => 10],
            ['categoria_id' => $bebidas, 'nombre' => 'Bolis',                 'precio_venta' => 2500,  'precio_costo' => 900,  'stock_actual' => 40, 'stock_minimo' => 10],

            // Aceites RF06 - 8 presentaciones
            ['categoria_id' => $aceites, 'nombre' => 'Aceite Coco 30ml',      'precio_venta' => 8000,  'precio_costo' => 4000, 'stock_actual' => 25, 'stock_minimo' => 8],
            ['categoria_id' => $aceites, 'nombre' => 'Aceite Coco 60ml',      'precio_venta' => 12000, 'precio_costo' => 6000, 'stock_actual' => 20, 'stock_minimo' => 8],
            ['categoria_id' => $aceites, 'nombre' => 'Aceite Coco 100ml',     'precio_venta' => 18000, 'precio_costo' => 9000, 'stock_actual' => 15, 'stock_minimo' => 5],
            ['categoria_id' => $aceites, 'nombre' => 'Aceite Coco 130ml',     'precio_venta' => 22000, 'precio_costo' => 11000, 'stock_actual' => 12, 'stock_minimo' => 5],
            ['categoria_id' => $aceites, 'nombre' => 'Aceite Coco 200ml',     'precio_venta' => 30000, 'precio_costo' => 15000, 'stock_actual' => 10, 'stock_minimo' => 4],
            ['categoria_id' => $aceites, 'nombre' => 'Aceite Coco 500ml',     'precio_venta' => 55000, 'precio_costo' => 28000, 'stock_actual' => 8,  'stock_minimo' => 3],
            ['categoria_id' => $aceites, 'nombre' => 'Aceite Coco 1 Litro',   'precio_venta' => 95000, 'precio_costo' => 50000, 'stock_actual' => 5,  'stock_minimo' => 2],
            ['categoria_id' => $aceites, 'nombre' => 'Aceite Cosmético 120ml', 'precio_venta' => 20000, 'precio_costo' => 10000, 'stock_actual' => 14, 'stock_minimo' => 5],

            // Productos de Coco RF06
            ['categoria_id' => $coco, 'nombre' => 'Leche de Coco 500ml',      'precio_venta' => 9000,  'precio_costo' => 4000, 'stock_actual' => 20, 'stock_minimo' => 5],
            ['categoria_id' => $coco, 'nombre' => 'Leche de Coco 1000ml',     'precio_venta' => 16000, 'precio_costo' => 7500, 'stock_actual' => 15, 'stock_minimo' => 5],
            ['categoria_id' => $coco, 'nombre' => 'Galón de Leche de Coco',   'precio_venta' => 55000, 'precio_costo' => 28000, 'stock_actual' => 6,  'stock_minimo' => 2],
            ['categoria_id' => $coco, 'nombre' => 'Torta de Coco',            'precio_venta' => 15000, 'precio_costo' => 7000, 'stock_actual' => 8,  'stock_minimo' => 3],
            ['categoria_id' => $coco, 'nombre' => 'Cocadas x6',               'precio_venta' => 7000,  'precio_costo' => 3000, 'stock_actual' => 25, 'stock_minimo' => 8],
            ['categoria_id' => $coco, 'nombre' => 'Hojuelas de Coco 200g',    'precio_venta' => 8000,  'precio_costo' => 3500, 'stock_actual' => 18, 'stock_minimo' => 5],
            ['categoria_id' => $coco, 'nombre' => 'Galletas de Coco',         'precio_venta' => 6000,  'precio_costo' => 2500, 'stock_actual' => 22, 'stock_minimo' => 6],
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }
    }
}
