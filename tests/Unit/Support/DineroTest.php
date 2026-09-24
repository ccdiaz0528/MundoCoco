<?php

use App\Support\Dinero;

describe('Dinero', function () {
    beforeEach(function () {
        $this->dinero = new class
        {
            use Dinero {
                aCentavos as public;
                desdeCentavos as public;
            }
        };
    });

    it('conserva el signo en montos negativos menores a un peso', function () {
        expect($this->dinero->desdeCentavos(-50))->toBe('-0.50')
            ->and($this->dinero->desdeCentavos(-5))->toBe('-0.05')
            ->and($this->dinero->desdeCentavos(-150))->toBe('-1.50');
    });

    it('formatea cero y positivos', function () {
        expect($this->dinero->desdeCentavos(0))->toBe('0.00')
            ->and($this->dinero->desdeCentavos(5))->toBe('0.05')
            ->and($this->dinero->desdeCentavos(9500000))->toBe('95000.00');
    });

    it('ida y vuelta exacta con coma o punto decimal', function () {
        expect($this->dinero->desdeCentavos($this->dinero->aCentavos('2000,5')))->toBe('2000.50')
            ->and($this->dinero->aCentavos('0.07'))->toBe(7);
    });
});
