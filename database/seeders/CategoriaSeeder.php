<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        // RF06 - Categorías requeridas MundoCoco (anteproyecto + Solución Sistema)
        $categorias = [
            [
                'nombre' => 'Helados',
                'descripcion' => 'Helados artesanales: Arequipe, Bocadillo, Brownie, Café, Chicle, Chocolate, Fresa, Guanábana, Mangoviche, Mora, Maní, Maracuyá, Ron con Pasas, Salpicón',
            ],
            [
                'nombre' => 'Bebidas',
                'descripcion' => 'Limonada, Mandarindada, Cerezada, Avena, Cocofee, Cocoloco, Cocobaileys, Jugo de Coco, Limonada Natural, Agua de Coco, Agua Natural, Agua con Gas, Bolis',
            ],
            [
                'nombre' => 'Aceites',
                'descripcion' => 'Aceites de coco presentaciones 30ml, 60ml, 100ml, 130ml, 200ml, 500ml, 1 Litro, Cosmético 120ml',
            ],
            [
                'nombre' => 'Productos de Coco',
                'descripcion' => 'Leche de Coco, PET 500/1000, Galón, Tortas, Cocadas, Hojuelas, Galletas',
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::firstOrCreate(['nombre' => $categoria['nombre']], $categoria);
        }
    }
}
