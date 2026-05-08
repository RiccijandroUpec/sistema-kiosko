<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Costos de Impresión
    |--------------------------------------------------------------------------
    |
    | Configurar los costos por página según el tipo de impresión
    | Los precios están en dólares USD
    |
    */

    'cost_bw' => env('PRINT_COST_BW', 0.05), // Blanco y negro
    'cost_color' => env('PRINT_COST_COLOR', 0.20), // Color

    /*
    |--------------------------------------------------------------------------
    | Configuración de Banco
    |--------------------------------------------------------------------------
    |
    | Detalles bancarios para mostrar en las instrucciones de pago
    |
    */

    'bank' => [
        'name' => env('BANK_NAME', 'Banco del Pacífico'),
        'account_number' => env('BANK_ACCOUNT', '123456789'),
        'account_type' => env('BANK_ACCOUNT_TYPE', 'Corriente'),
        'ruc' => env('BANK_RUC', '0123456789001'),
        'currency' => env('BANK_CURRENCY', 'USD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Límites del Sistema
    |--------------------------------------------------------------------------
    |
    | Configurar límites y restricciones
    |
    */

    'max_file_size_mb' => env('MAX_FILE_SIZE_MB', 10),
    'max_copies' => env('MAX_COPIES', 999),
];
