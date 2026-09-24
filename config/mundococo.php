<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administrador inicial (AdminUserSeeder)
    |--------------------------------------------------------------------------
    | Credenciales del usuario Admin creado al sembrar. Se leen de .env
    | para nunca versionar claves. Ver .env.example (ADMIN_*).
    |
    */

    'admin_name' => env('ADMIN_NAME', 'Administrador'),
    'admin_email' => env('ADMIN_EMAIL', ''),
    'admin_password' => env('ADMIN_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Precios de venta (RF01 / RF04)
    |--------------------------------------------------------------------------
    | Rango permitido para el precio de catálogo y para el precio aplicado en
    | una venta cuando difiere del precio base (RNF06: configurable sin tocar
    | código).
    |
    */

    'precio_venta_min' => (int) env('PRECIO_VENTA_MIN', 2000),
    'precio_venta_max' => (int) env('PRECIO_VENTA_MAX', 95000),

    /*
    |--------------------------------------------------------------------------
    | Reorden de inventario (RF07)
    |--------------------------------------------------------------------------
    | La sugerencia de reorden repone hasta stock_minimo × factor_reorden.
    |
    */

    'factor_reorden' => (float) env('FACTOR_REORDEN', 2),

    /*
    |--------------------------------------------------------------------------
    | Protección de datos (Ley 1581 de 2012)
    |--------------------------------------------------------------------------
    | Canal para que los titulares ejerzan sus derechos (habeas data).
    |
    */

    'contacto_datos' => env('CONTACTO_DATOS', 'datos@mundococo.com'),

];
