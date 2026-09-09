<?php

use Illuminate\Support\Facades\Storage;

describe('RNF04 respaldo diario de base de datos', function () {
    it('genera un volcado SQL en storage/app/backups', function () {
        Storage::fake('local');

        $this->artisan('backup:database')->assertSuccessful();

        $archivos = Storage::disk('local')->files('backups');
        expect($archivos)->not->toBeEmpty();

        $contenido = Storage::disk('local')->get($archivos[0]);
        expect($contenido)->toContain('CREATE TABLE')
            ->and($contenido)->toContain('productos');
    });

    it('purga copias más antiguas que la retención', function () {
        Storage::fake('local');
        Storage::disk('local')->put('backups/backup-antiguo.sql', 'viejo');

        $this->artisan('backup:database')->assertSuccessful();
        expect(Storage::disk('local')->files('backups'))->not->toBeEmpty();

        $this->artisan('backup:database', ['--retencion' => -1])->assertSuccessful();
        expect(Storage::disk('local')->files('backups'))->toBeEmpty();
    });
});
