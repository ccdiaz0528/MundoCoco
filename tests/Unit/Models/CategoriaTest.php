<?php

use App\Models\Categoria;
use App\Models\Producto;

describe('Categoria', function () {
    it('puede crear una categoría', function () {
        $categoria = Categoria::factory()->create([
            'nombre' => 'Electrónica',
        ]);

        expect($categoria->nombre)->toBe('Electrónica');
        expect($categoria->id)->toBeGreaterThan(0);
    });

    it('una categoría puede tener muchos productos', function () {
        $categoria = Categoria::factory()->create();
        $productos = Producto::factory(3)->create(['categoria_id' => $categoria->id]);

        expect($categoria->productos()->count())->toBe(3);
        expect($categoria->productos->first()->nombre)->toEqual($productos->first()->nombre);
    });

    it('puede filtrar categorías activas', function () {
        Categoria::factory()->create(['activa' => false]);
        Categoria::factory(2)->create(['activa' => true]);

        $categoriasActivas = Categoria::where('activa', true)->get();

        expect($categoriasActivas->count())->toBe(2);
    });

    it('requiere un nombre', function () {
        $categoria = Categoria::factory()->make(['nombre' => '']);

        expect($categoria->nombre)->toBeEmpty();
    });
});
