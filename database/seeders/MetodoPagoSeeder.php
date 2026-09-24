<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use Illuminate\Database\Seeder;

class MetodoPagoSeeder extends Seeder
{
    /**
     * RF04/RF11 (Efectivo, Nequi) + anteproyecto (transferencias bancarias,
     * tarjetas). Idempotente: se puede re-ejecutar en bases existentes.
     */
    public function run(): void
    {
        foreach (MetodoPago::NOMBRES as $nombre) {
            MetodoPago::firstOrCreate(['nombre' => $nombre], ['activo' => true]);
        }
    }
}
