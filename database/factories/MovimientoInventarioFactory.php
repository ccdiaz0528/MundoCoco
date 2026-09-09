<?php

namespace Database\Factories;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovimientoInventario>
 */
class MovimientoInventarioFactory extends Factory
{
    protected $model = MovimientoInventario::class;

    public function definition(): array
    {
        $tipos = [
            MovimientoInventario::TIPO_COMPRA,
            MovimientoInventario::TIPO_VENTA,
            MovimientoInventario::TIPO_AJUSTE_POSITIVO,
            MovimientoInventario::TIPO_AJUSTE_NEGATIVO,
        ];

        return [
            'producto_id' => Producto::factory(),
            'user_id' => User::factory(),
            'tipo' => fake()->randomElement($tipos),
            'cantidad' => fake()->numberBetween(1, 10),
            'stock_anterior' => fake()->numberBetween(10, 50),
            'stock_nuevo' => fake()->numberBetween(10, 50),
            'motivo' => fake()->sentence(),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }

    public function venta(): static
    {
        return $this->state(fn (array $attributes) => ['tipo' => MovimientoInventario::TIPO_VENTA]);
    }

    public function compra(): static
    {
        return $this->state(fn (array $attributes) => ['tipo' => MovimientoInventario::TIPO_COMPRA]);
    }
}
