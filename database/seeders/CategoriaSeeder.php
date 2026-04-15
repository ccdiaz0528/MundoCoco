<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Helados',    'descripcion' => 'Helados artesanales y de paleta'],
            ['nombre' => 'Postres',    'descripcion' => 'Postres fríos y calientes'],
            ['nombre' => 'Bebidas',    'descripcion' => 'Jugos, malteadas y bebidas frías'],
            ['nombre' => 'Ingredientes', 'descripcion' => 'Ingredientes para preparación'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }
    }
}
