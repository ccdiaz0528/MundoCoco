<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Configuración base de las pruebas
|--------------------------------------------------------------------------
| Las pruebas de Feature y Unit usan TestCase y RefreshDatabase
| (sqlite en memoria, ver phpunit.xml).
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');
