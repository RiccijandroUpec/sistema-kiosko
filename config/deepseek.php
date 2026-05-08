<?php

return [
    // API key for Deepseek
    'api_key' => env('DEEPSEEK_API_KEY'),

    // Deepseek chat endpoint (adjust if Deepseek provides a different path)
    'endpoint' => env('DEEPSEEK_ENDPOINT', 'https://api.deepseek.ai/v1/chat'),
];
