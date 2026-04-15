<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Caja>
 */
class CajaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $saldoInicial = fake()->randomFloat(2, 100, 1000);

        return [
            'fecha' => now()->toDateString(),
            'estado' => 'abierta',
            'saldo_inicial' => $saldoInicial,
            'total_efectivo' => 0,
            'total_transferencias' => 0,
            'total_tarjetas' => 0,
            'total_ventas' => 0,
            'saldo_real' => $saldoInicial,
            'diferencia' => 0,
            'observaciones' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indica que la caja está cerrada.
     */
    public function cerrada()
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cerrada',
        ]);
    }
}
