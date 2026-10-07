<?php

use App\Filament\Pages\Reportes;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/*
 * Verificación de requisitos no funcionales que no dependen de la interfaz:
 * RNF04 (sesión y contraseñas) y RNF09 (carga de la página de reportes con volumen).
 * La concurrencia (RNF01), el tamaño de la base de datos (RNF09) y los navegadores
 * (RNF03) se miden contra un servidor real; ver docs/TESTING_REPORT.md.
 */

describe('RNF04: sesión y credenciales', function () {
    it('la sesión expira tras 120 minutos (2 horas) de inactividad', function () {
        expect(config('session.lifetime'))->toBe(120)
            ->and(config('session.expire_on_close'))->toBeFalse();
    });

    it('el archivo de ejemplo del entorno conserva el cierre por inactividad de 2 horas', function () {
        expect(file_get_contents(base_path('.env.example')))->toContain('SESSION_LIFETIME=120');
    });

    it('las contraseñas se almacenan cifradas con bcrypt', function () {
        $usuario = User::factory()->create(['password' => 'ClaveSegura2026!']);

        expect(Hash::info($usuario->password)['algoName'])->toBe('bcrypt')
            ->and($usuario->password)->not->toBe('ClaveSegura2026!')
            ->and(Hash::check('ClaveSegura2026!', $usuario->password))->toBeTrue();
    });
});

describe('RNF09: la página de reportes se mantiene liviana con muchos movimientos', function () {
    it('dibuja como máximo 200 movimientos en pantalla e informa el total del periodo', function () {
        $this->seed(RoleSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $usuario = tap(User::factory()->create())->assignRole('Consultor');
        $producto = Producto::factory()->create();
        MovimientoInventario::factory()->count(230)->create([
            'producto_id' => $producto->id,
            'user_id' => $usuario->id,
            'fecha_movimiento' => now()->subDay(),
        ]);

        $total = MovimientoInventario::count();
        $this->actingAs($usuario);
        $pagina = Livewire::test(Reportes::class)
            ->assertOk()
            ->assertSee("{$total} movimientos; se muestran los 200 más recientes");
        $datos = $pagina->instance()->getViewData();

        expect($total)->toBeGreaterThanOrEqual(230)
            ->and($datos['totalMovimientos'])->toBe($total)
            ->and($datos['movimientos'])->toHaveCount(200);
    });

    it('con pocos movimientos muestra todos y no menciona el límite', function () {
        $this->seed(RoleSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $usuario = tap(User::factory()->create())->assignRole('Consultor');
        MovimientoInventario::factory()->count(5)->create([
            'producto_id' => Producto::factory()->create()->id,
            'user_id' => $usuario->id,
            'fecha_movimiento' => now()->subDay(),
        ]);

        $total = MovimientoInventario::count();
        $this->actingAs($usuario);
        Livewire::test(Reportes::class)
            ->assertOk()
            ->assertSee("({$total} movimientos)")
            ->assertDontSee('se muestran los');
    });
});
