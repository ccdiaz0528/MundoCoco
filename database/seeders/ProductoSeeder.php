<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;

/**
 * RF06: catálogo real de MundoCoco tomado de la planilla física de
 * inventario y venta de la tienda (MundoCoco Campestre). Los precios de
 * venta son las tarifas impresas en esa planilla.
 *
 * El stock arranca en 0 y el costo vacío: la tienda los fija con su primer
 * conteo físico (Inventario > Conteo físico, modo "inventario inicial") y
 * editando cada producto. No se inventan existencias ni costos.
 */
class ProductoSeeder extends Seeder
{
    /** Nombre => precio de venta, por categoría (RF06). */
    public const CATALOGO = [
        'Helados' => [
            'Helado de Coco' => 4500,
            'Helado Cocomaní' => 4500,
            'Helado Cocoqueso' => 4500,
            'Helado Cocoestevia' => 4500,
            'Helado de Arequipe' => 4000,
            'Helado de Bocadillo' => 4000,
            'Helado de Brownie' => 4000,
            'Helado de Café' => 4000,
            'Helado de Chicle' => 4000,
            'Helado de Chocolate' => 4000,
            'Helado de Fresa' => 4000,
            'Helado de Guanábana' => 4000,
            'Helado de Mangoviche' => 4000,
            'Helado de Mora' => 4000,
            'Helado de Maní' => 4000,
            'Helado de Maracuyá' => 4000,
            'Helado de Ron con Pasas' => 4000,
            'Helado de Salpicón' => 4000,
            'Helado para Perro' => 4000,
        ],
        'Bebidas' => [
            'Limonada' => 7000,
            'Mandarinada' => 7000,
            'Cerezada' => 7000,
            'Avena' => 7000,
            'Cocofee' => 7000,
            'Cocoloco' => 14000,
            'Cocobaileys' => 12000,
            'Jugo de Coco' => 7000,
            'Limonada Natural' => 4000,
            'Agua de Coco Vaso 9 oz' => 3000,
            'Agua Natural' => 2000,
            'Agua con Gas' => 2500,
            'Bolis' => 2000,
            'Agua de Coco PET 500 ml' => 6000,
            'Agua de Coco PET 1000 ml' => 12000,
            'Galón de Agua de Coco' => 40000,
        ],
        'Aceites' => [
            'Aceite de Coco 30 ml' => 6000,
            'Aceite de Coco 60 ml' => 9000,
            'Aceite de Coco 100 ml' => 14000,
            'Aceite de Coco 140 ml' => 19000,
            'Aceite de Coco 200 ml' => 26000,
            'Aceite de Coco 500 ml' => 55000,
            'Aceite de Coco 1 Litro' => 95000,
            'Aceite Cosmético 120 ml' => 11000,
        ],
        'Productos de Coco' => [
            'Leche de Coco 250 g' => 6500,
            'Torta de Coco' => 4000,
            'Torta de Naranja' => 4000,
            'Hojuelas de Coco' => 3500,
            'Galletas de Coco' => 3500,
            'Corchitos' => 3500,
            'Cocadas' => 5000,
        ],
    ];

    public function run(): void
    {
        foreach (self::CATALOGO as $categoria => $productos) {
            $categoriaId = Categoria::where('nombre', $categoria)->firstOrFail()->id;

            foreach ($productos as $nombre => $precio) {
                // Idempotente: no duplica si se re-ejecuta; respeta SoftDeletes y
                // no sobrescribe stock/codigo/precio ya ajustados en la tienda.
                // El código se asigna aquí porque DatabaseSeeder corre sin eventos
                // de modelo (el autogenerado de Producto::booted no se dispara).
                $producto = Producto::withTrashed()->firstOrNew(['nombre' => $nombre, 'categoria_id' => $categoriaId]);
                if (! $producto->exists) {
                    $producto->fill(['precio_venta' => $precio, 'precio_costo' => null, 'stock_actual' => 0, 'stock_minimo' => 5]);
                }
                $producto->codigo ??= Producto::generarCodigo();
                $producto->save();
            }
        }
    }
}
