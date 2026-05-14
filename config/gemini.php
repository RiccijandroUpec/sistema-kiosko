<?php

return [
    'api_key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
    'system_instruction' => env('GEMINI_SYSTEM_INSTRUCTION', 'Eres un asistente breve y útil para un kiosko de impresiones. Responde en español y de forma concisa.'),
];