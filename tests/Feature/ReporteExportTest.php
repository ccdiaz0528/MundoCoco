<?php

use App\Filament\Pages\Reportes;
use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Services\ReporteExportService;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;

describe('RF08/RF09 exportación de reportes', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        $consultor = User::factory()->create();
        $consultor->assignRole('Consultor');
        $this->actingAs($consultor);
        $categoria = Categoria::factory()->create(['nombre' => 'Helados']);
        Producto::factory()->create(['categoria_id' => $categoria->id, 'stock_actual' => 10, 'stock_minimo' => 5]);
    });

    it('exporta todos los reportes en CSV', function () {
        foreach (array_keys(ReporteExportService::TIPOS) as $tipo) {
            $respuesta = $this->get("/reportes/export/{$tipo}/csv");
            $respuesta->assertOk();
            expect($respuesta->headers->get('Content-Type'))->toContain('text/csv')
                ->and($respuesta->headers->get('Content-Disposition'))->toContain('.csv');
        }

        expect($this->get('/reportes/export/inventario/csv')->streamedContent())
            ->toContain('Categoría')
            ->toContain('Helados')
            ->toContain('10');
    });

    it('RF08: exporta todos los reportes en PDF válido', function () {
        foreach (array_keys(ReporteExportService::TIPOS) as $tipo) {
            $respuesta = $this->get("/reportes/export/{$tipo}/pdf");
            $respuesta->assertOk();
            expect($respuesta->headers->get('Content-Type'))->toContain('application/pdf')
                ->and(substr($respuesta->getContent(), 0, 4))->toBe('%PDF');
        }
    });

    it('RF08: exporta todos los reportes en Excel válido', function () {
        foreach (array_keys(ReporteExportService::TIPOS) as $tipo) {
            $respuesta = $this->get("/reportes/export/{$tipo}/xlsx");
            $respuesta->assertOk();
            expect($respuesta->headers->get('Content-Type'))->toContain('spreadsheetml')
                ->and(substr($respuesta->streamedContent(), 0, 2))->toBe('PK');
        }
    });

    it('RF08: conserva las cantidades y los centavos del inventario en Excel y CSV para cualquier periodo', function () {
        Producto::query()->update(['precio_costo' => '1000.25', 'precio_venta' => '2000.50']);
        $rango = '?desde=2020-01-01&hasta=2020-01-31';

        $csv = $this->get('/reportes/export/inventario/csv'.$rango)->streamedContent();
        expect($csv)->toContain('Helados')->toContain('10002.50')->toContain('20005.00');

        $xlsx = $this->get('/reportes/export/inventario/xlsx'.$rango)->streamedContent();
        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($ruta, $xlsx);

        try {
            $hoja = IOFactory::load($ruta)->getActiveSheet();
            expect($hoja->getCell('A2')->getValue())->toBe('Helados')
                ->and((int) $hoja->getCell('C2')->getValue())->toBe(10)
                ->and((float) $hoja->getCell('E2')->getValue())->toBe(10002.50)
                ->and((float) $hoja->getCell('F2')->getValue())->toBe(20005.00);
        } finally {
            unlink($ruta);
        }
    });

    it('RF08: aplica el mismo periodo a los movimientos en pantalla y CSV', function () {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $producto = Producto::query()->firstOrFail();
        $dentro = MovimientoInventario::factory()->create([
            'producto_id' => $producto->id,
            'user_id' => auth()->id(),
            'fecha_movimiento' => '2020-01-15 12:00:00',
            'motivo' => 'Movimiento dentro del periodo',
        ]);
        $fuera = MovimientoInventario::factory()->create([
            'producto_id' => $producto->id,
            'user_id' => auth()->id(),
            'fecha_movimiento' => '2020-02-01 12:00:00',
            'motivo' => 'Movimiento fuera del periodo',
        ]);

        $pagina = Livewire::test(Reportes::class)
            ->set('desde', '2020-01-01')
            ->set('hasta', '2020-01-31')
            ->call('filtrar')
            ->assertHasNoErrors();
        $ids = $pagina->instance()->getViewData()['movimientos']->modelKeys();
        expect($ids)->toContain($dentro->id)->not->toContain($fuera->id);

        $csv = $this->get('/reportes/export/movimientos/csv?desde=2020-01-01&hasta=2020-01-31')->streamedContent();
        expect($csv)->toContain('Movimiento dentro del periodo')->not->toContain('Movimiento fuera del periodo');

        $xlsx = $this->get('/reportes/export/movimientos/xlsx?desde=2020-01-01&hasta=2020-01-31')->streamedContent();
        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($ruta, $xlsx);
        try {
            $filas = IOFactory::load($ruta)->getActiveSheet()->toArray();
            $motivos = array_column($filas, 8);
            expect($motivos)->toContain('Movimiento dentro del periodo')->not->toContain('Movimiento fuera del periodo');
        } finally {
            unlink($ruta);
        }
    });

    it('rechaza tipo o formato desconocido con 404', function () {
        $this->get('/reportes/export/inventario/docx')->assertNotFound();
        $this->get('/reportes/export/otro/csv')->assertNotFound();
    });

    it('exige autenticación para exportar', function () {
        auth()->logout();
        $this->get('/reportes/export/inventario/csv')->assertRedirect();
    });

    it('exige el permiso exportar reportes', function () {
        $this->actingAs(User::factory()->create());

        $this->get('/reportes/export/inventario/csv')->assertForbidden();
    });

    it('rechaza fechas mal formadas o un rango invertido con 422', function () {
        $this->get('/reportes/export/ventas/csv?desde=ayer')->assertStatus(422);
        $this->get('/reportes/export/ventas/csv?desde=2026-09-10&hasta=2026-09-01')->assertStatus(422);
    });

    it('acepta un rango válido o solo la fecha final', function () {
        $this->get('/reportes/export/ventas/csv?desde=2026-09-01&hasta=2026-09-10')->assertOk();
        $this->get('/reportes/export/ventas/csv?hasta=2026-09-10')->assertOk();
    });
});
