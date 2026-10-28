<?php

use App\Filament\Resources\Movimientos\Pages\ListMovimientos;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $usuario = User::factory()->create();
    $usuario->assignRole('Admin');
    $this->actingAs($usuario);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('abre el detalle del movimiento en un modal de solo lectura, igual que en ventas', function () {
    $producto = Producto::factory()->create(['nombre' => 'Cocada', 'codigo' => 'P-COCADA']);
    $movimiento = MovimientoInventario::factory()->compra()->create([
        'producto_id' => $producto->id,
        'motivo' => 'Compra proveedor',
    ]);

    Livewire::test(ListMovimientos::class)
        ->assertTableActionExists('view', record: $movimiento)
        ->assertTableActionVisible('view', $movimiento)
        ->mountTableAction('view', $movimiento)
        ->assertActionMounted();
});
