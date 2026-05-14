<?php

return [
    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    // The WhatsApp-enabled Twilio number, e.g. "whatsapp:+1415XXXXXXX"
    'from_whatsapp' => env('TWILIO_FROM_WHATSAPP'),
    // WhatsApp number without whatsapp: prefix (for QR generation)
    'whatsapp_number' => env('TWILIO_WHATSAPP_NUMBER', '+14155238886'),
    // Enable sandbox behavior (QR pre-fills "join <code>")
    'use_sandbox' => env('TWILIO_USE_SANDBOX', true),
    // Twilio Sandbox join code (without the word "join")
    'sandbox_join_code' => env('TWILIO_SANDBOX_JOIN_CODE', ''),
    // Default text prefilled when the QR opens WhatsApp
    'whatsapp_message' => env('TWILIO_WHATSAPP_MESSAGE', 'Hola, quiero imprimir un PDF'),
];
