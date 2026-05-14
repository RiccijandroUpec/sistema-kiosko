<?php

return [
    'base_url' => env('EVOLUTION_API_BASE_URL', 'http://127.0.0.1:8080'),
    'api_key' => env('EVOLUTION_API_KEY'),
    'instance' => env('EVOLUTION_INSTANCE', 'kiosko'),
    'whatsapp_number' => env('EVOLUTION_WHATSAPP_NUMBER', '+14155238886'),
    'whatsapp_message' => env('EVOLUTION_WHATSAPP_MESSAGE', 'Hola, quiero imprimir un PDF'),
];
