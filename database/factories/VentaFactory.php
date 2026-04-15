<?php

namespace Database\Factories;

use App\Models\MetodoPago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venta>
 */
class VentaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'metodo_pago_id' => MetodoPago::factory(),
            'total' => fake()->randomFloat(2, 50, 500),
            'observaciones' => fake()->optional()->sentence(),
            'fecha_venta' => fake()->dateTime(),
        ];
    }
}
