<?php

namespace Database\Factories;

use App\Models\Gasto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gasto>
 */
class GastoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fecha' => now()->toDateString(),
            'descripcion' => fake()->sentence(3),
            'categoria' => fake()->randomElement(array_keys(Gasto::CATEGORIAS)),
            'monto' => fake()->randomFloat(2, 5000, 50000),
            'user_id' => User::factory(),
            'caja_id' => null,
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
