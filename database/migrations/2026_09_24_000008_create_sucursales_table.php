<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RNF07 "preparado para expansión a múltiples puntos de venta": las
 * operaciones quedan ligadas a una sucursal. Hoy existe una sola (la
 * principal) y todo se asigna a ella; abrir otra no requiere migrar datos.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private array $tablas = ['ventas', 'caja', 'gastos', 'movimientos_inventario'];

    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->string('direccion')->nullable();
            $table->boolean('principal')->default(false);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        $principal = DB::table('sucursales')->insertGetId([
            'nombre' => 'MundoCoco Principal',
            'direccion' => 'Cali, Colombia',
            'principal' => true,
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('sucursal_id')->nullable()->after('id')->constrained('sucursales')->restrictOnDelete();
            });
            DB::table($tabla)->whereNull('sucursal_id')->update(['sucursal_id' => $principal]);
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('sucursal_id');
            });
        }

        Schema::dropIfExists('sucursales');
    }
};
