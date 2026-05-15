<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepseekService
{
    /**
     * Send a message to Deepseek and return a text reply.
     * Uses Deepseek's OpenAI-compatible API.
     */
    public function chat(string $message, array $context = []): string
    {
        $apiKey = config('deepseek.api_key');
        $endpoint = config('deepseek.endpoint', 'https://api.deepseek.com/chat/completions');
        $model = config('deepseek.model', 'deepseek-chat');

        if (empty($apiKey)) {
            return $this->fallbackReply($message);
        }

        try {
            $payload = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un asistente experto para un kiosko de impresiones llamado "RickTech". Responde en español de forma concisa. 
                        Si el usuario indica cuántas copias quiere o si desea a color/blanco y negro, responde amablemente y AL FINAL de tu respuesta incluye SIEMPRE un bloque JSON con este formato exacto: 
                        {"config": {"copies": número, "color_type": "bw" o "color"}}.
                        Si no detectas intención de configuración, no incluyas el JSON.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $message,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 500,
            ];

            $response = Http::acceptJson()
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                ])
                ->timeout(10)
                ->post($endpoint, $payload);

            if ($response->ok()) {
                $json = $response->json();
                
                if (isset($json['choices'][0]['message']['content'])) {
                    $content = trim($json['choices'][0]['message']['content']);
                    if (!empty($content)) {
                        return $content;
                    }
                }

                Log::warning('Unexpected Deepseek response format', ['response' => $json]);
                return $this->fallbackReply($message);
            }

            Log::error('Deepseek API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->status() === 401) {
                return 'Deepseek no está autorizado. Revisa la API key en config/deepseek.php';
            }

            if ($response->status() === 429) {
                return 'Deepseek está sobrecargado. Intenta de nuevo en unos segundos.';
            }

            if ($response->status() === 500) {
                return 'Deepseek está experimentando problemas. Intenta de nuevo más tarde.';
            }

            return $this->fallbackReply($message);
        } catch (\Exception $e) {
            Log::error('Deepseek request failed', ['error' => $e->getMessage()]);
            return $this->fallbackReply($message);
        }
    }

    private function fallbackReply(string $message): string
    {
        return "Recibí tu mensaje: {$message}. En este momento no pude consultar la IA, pero puedo ayudarte con la impresión.";
    }
}
