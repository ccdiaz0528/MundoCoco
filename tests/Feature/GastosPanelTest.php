<?php

use App\Filament\Resources\Gastos\Pages\EditGasto;
use App\Models\Caja;
use App\Models\Gasto;
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

it('edita un gasto de caja abierta y recalcula sus totales', function () {
    $caja = Caja::factory()->create(['fecha' => '2026-10-20', 'saldo_inicial' => 100000]);
    $gasto = Gasto::factory()->create(['fecha' => '2026-10-20', 'monto' => 10000]);

    Livewire::test(EditGasto::class, ['record' => $gasto->getRouteKey()])
        ->fillForm(['descripcion' => 'Gasto corregido', 'monto' => 15000])
        ->call('save')->assertHasNoFormErrors();

    expect($gasto->fresh()->descripcion)->toBe('Gasto corregido')
        ->and((string) $caja->fresh()->total_gastos)->toBe('15000.00')
        ->and((string) $caja->fresh()->saldo_teorico)->toBe('85000.00');
});

it('recalcula ambas cajas abiertas al cambiar la fecha de un gasto', function () {
    $origen = Caja::factory()->create(['fecha' => '2026-10-20', 'saldo_inicial' => 100000]);
    $destino = Caja::factory()->create(['fecha' => '2026-10-21', 'saldo_inicial' => 80000]);
    $gasto = Gasto::factory()->create(['fecha' => '2026-10-20', 'monto' => 10000]);

    Livewire::test(EditGasto::class, ['record' => $gasto->getRouteKey()])
        ->fillForm(['fecha' => '2026-10-21'])
        ->call('save')->assertHasNoFormErrors();

    expect($gasto->fresh()->fecha->toDateString())->toBe('2026-10-21')
        ->and((string) $origen->fresh()->total_gastos)->toBe('0.00')
        ->and((string) $origen->fresh()->saldo_teorico)->toBe('100000.00')
        ->and((string) $destino->fresh()->total_gastos)->toBe('10000.00')
        ->and((string) $destino->fresh()->saldo_teorico)->toBe('70000.00');
});

it('impide mover un gasto a una caja cerrada sin guardar cambios parciales', function () {
    $origen = Caja::factory()->create(['fecha' => '2026-10-20', 'saldo_inicial' => 100000]);
    $destino = Caja::factory()->cerrada()->create(['fecha' => '2026-10-21', 'saldo_inicial' => 80000]);
    $gasto = Gasto::factory()->create(['fecha' => '2026-10-20', 'monto' => 10000, 'descripcion' => 'Original']);
    $saldoCerrado = $destino->fresh()->saldo_teorico;

    Livewire::test(EditGasto::class, ['record' => $gasto->getRouteKey()])
        ->fillForm(['fecha' => '2026-10-21', 'descripcion' => 'No guardar', 'monto' => 15000])
        ->call('save')->assertHasFormErrors(['fecha']);

    expect($gasto->fresh()->fecha->toDateString())->toBe('2026-10-20')
        ->and($gasto->fresh()->descripcion)->toBe('Original')
        ->and((string) $gasto->fresh()->monto)->toBe('10000.00')
        ->and((string) $origen->fresh()->total_gastos)->toBe('10000.00')
        ->and($destino->fresh()->saldo_teorico)->toBe($saldoCerrado);
});

it('mantiene separado un retiro al editar su monto', function () {
    $caja = Caja::factory()->create(['fecha' => '2026-10-20', 'saldo_inicial' => 100000]);
    $retiro = Gasto::factory()->create(['fecha' => '2026-10-20', 'categoria' => Gasto::RETIRO, 'monto' => 10000]);

    Livewire::test(EditGasto::class, ['record' => $retiro->getRouteKey()])
        ->fillForm(['monto' => 12000])
        ->call('save')->assertHasNoFormErrors();

    expect((string) $caja->fresh()->total_gastos)->toBe('0.00')
        ->and((string) $caja->fresh()->total_retiros)->toBe('12000.00')
        ->and((string) $caja->fresh()->saldo_teorico)->toBe('88000.00');
});

it('no permite abrir la edición de un gasto de caja cerrada', function () {
    Caja::factory()->cerrada()->create(['fecha' => '2026-10-20']);
    $gasto = Gasto::factory()->create(['fecha' => '2026-10-20']);

    Livewire::test(EditGasto::class, ['record' => $gasto->getRouteKey()])->assertForbidden();
});
