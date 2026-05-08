<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepseekService
{
    /**
     * Send a message to Deepseek and return a text reply.
     * Adjust payload/response parsing according to Deepseek's actual API.
     */
    public function chat(string $message, array $context = []): string
    {
        $apiKey = config('deepseek.api_key');
        $endpoint = config('deepseek.endpoint');

        try {
            $response = Http::withToken($apiKey)
                ->post($endpoint, [
                    'message' => $message,
                    'context' => $context,
                ]);

            if ($response->ok()) {
                $json = $response->json();
                // Expecting Deepseek to return something like ['reply' => '...']
                if (is_array($json) && isset($json['reply'])) {
                    return $json['reply'];
                }

                // fallback: try `message` or raw text
                return is_string($json) ? $json : ($json['message'] ?? json_encode($json));
            }

            Log::error('Deepseek API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Deepseek API returned status ' . $response->status());
        } catch (\Exception $e) {
            Log::error('Deepseek request failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
