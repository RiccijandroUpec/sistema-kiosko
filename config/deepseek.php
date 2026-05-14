<?php

return [
    // API key for Deepseek
    'api_key' => env('DEEPSEEK_API_KEY'),

    // Deepseek OpenAI-compatible chat endpoint
    'endpoint' => env('DEEPSEEK_ENDPOINT', 'https://api.deepseek.com/chat/completions'),
    'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
];
