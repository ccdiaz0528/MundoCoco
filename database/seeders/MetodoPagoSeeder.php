<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use Illuminate\Database\Seeder;

class MetodoPagoSeeder extends Seeder
{
    public function run(): void
    {
        $metodos = [
            ['nombre' => 'Efectivo'],
            ['nombre' => 'Transferencia'],
            ['nombre' => 'Tarjeta'],
        ];

        foreach ($metodos as $metodo) {
            MetodoPago::create($metodo);
        }
    }
}
