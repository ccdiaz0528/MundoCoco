<?php

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Services\ReporteExportService;
use Database\Seeders\RoleSeeder;

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

        expect($this->get('/reportes/export/inventario/csv')->streamedContent())->toContain('Categoría');
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
