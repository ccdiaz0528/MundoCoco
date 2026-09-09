<?php

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;

describe('RF08/RF09 exportación de reportes', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create());
        $categoria = Categoria::factory()->create(['nombre' => 'Helados']);
        Producto::factory()->create(['categoria_id' => $categoria->id, 'stock_actual' => 10, 'stock_minimo' => 5]);
    });

    it('exporta inventario y ventas en CSV', function () {
        foreach (['inventario', 'ventas'] as $tipo) {
            $respuesta = $this->get("/reportes/export/{$tipo}/csv");
            $respuesta->assertOk();
            expect($respuesta->headers->get('Content-Type'))->toContain('text/csv')
                ->and($respuesta->headers->get('Content-Disposition'))->toContain('.csv');
        }

        expect($this->get('/reportes/export/inventario/csv')->streamedContent())->toContain('Categoria');
    });

    it('exporta inventario y ventas en PDF válido', function () {
        foreach (['inventario', 'ventas'] as $tipo) {
            $respuesta = $this->get("/reportes/export/{$tipo}/pdf");
            $respuesta->assertOk();
            expect($respuesta->headers->get('Content-Type'))->toContain('application/pdf')
                ->and(substr($respuesta->getContent(), 0, 4))->toBe('%PDF');
        }
    });

    it('exporta inventario y ventas en Excel válido', function () {
        foreach (['inventario', 'ventas'] as $tipo) {
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
});
