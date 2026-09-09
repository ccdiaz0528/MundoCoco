<?php

use App\Models\Producto;
use Illuminate\Database\QueryException;

describe('RF01 código único de producto', function () {
    it('genera un código automáticamente al crear sin código', function () {
        $producto = Producto::factory()->create(['codigo' => null]);

        expect($producto->codigo)->not->toBeNull()
            ->and($producto->codigo)->toStartWith('P-');
    });

    it('conserva el código indicado manualmente', function () {
        $producto = Producto::factory()->create(['codigo' => 'HEL-0001']);

        expect($producto->fresh()->codigo)->toBe('HEL-0001');
    });

    it('rechaza códigos duplicados en el catálogo', function () {
        Producto::factory()->create(['codigo' => 'BEB-0007']);

        expect(fn () => Producto::factory()->create(['codigo' => 'BEB-0007']))
            ->toThrow(QueryException::class);
    });

    it('los códigos generados no colisionan en lote', function () {
        $codigos = Producto::factory(25)->create(['codigo' => null])
            ->pluck('codigo');

        expect($codigos->unique()->count())->toBe(25);
    });
});
