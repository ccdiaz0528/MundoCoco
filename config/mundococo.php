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

];
