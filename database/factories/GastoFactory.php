<?php

namespace Database\Factories;

use App\Models\Gasto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

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
            // Un retiro no es gasto: se crea explícitamente con ->state(['categoria' => Gasto::RETIRO]).
            'categoria' => fake()->randomElement(array_keys(Arr::except(Gasto::CATEGORIAS, Gasto::RETIRO))),
            'monto' => fake()->randomFloat(2, 5000, 50000),
            'user_id' => User::factory(),
            'caja_id' => null,
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
