<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API Configuration (Meta)
    |--------------------------------------------------------------------------
    */

    'token' => env('WHATSAPP_BUSINESS_TOKEN'),
    'phone_id' => env('WHATSAPP_BUSINESS_PHONE_ID'),
    'account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'phone_number' => env('WHATSAPP_BUSINESS_PHONE_NUMBER'),
    'api_version' => env('WHATSAPP_API_VERSION', 'v18.0'),
    'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */

    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'kiosko_webhook_token_2024'),
];
