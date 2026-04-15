<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $precioCosto = fake()->randomFloat(2, 10, 100);
        $precioVenta = $precioCosto * fake()->randomFloat(1, 1.2, 2.0);

        return [
            'categoria_id' => Categoria::factory(),
            'nombre' => fake()->unique()->word(),
            'descripcion' => fake()->sentence(),
            'precio_costo' => $precioCosto,
            'precio_venta' => round($precioVenta, 2),
            'stock_actual' => fake()->numberBetween(0, 100),
            'stock_minimo' => fake()->numberBetween(5, 20),
            'activo' => true,
        ];
    }

    /**
     * Indica que el producto está inactivo.
     */
    public function inactivo()
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Indica que el stock está bajo.
     */
    public function stockBajo()
    {
        return $this->state(fn (array $attributes) => [
            'stock_actual' => 2,
            'stock_minimo' => 10,
        ]);
    }
}
