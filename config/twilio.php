<?php

return [
    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    // The WhatsApp-enabled Twilio number, e.g. "whatsapp:+1415XXXXXXX"
    'from_whatsapp' => env('TWILIO_FROM_WHATSAPP'),
];
