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
        $apiKey = config('gemini.api_key');
        $model = config('gemini.model', 'gemini-2.5-flash');
        $endpointBase = rtrim((string) config('gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta/models'), '/');
        $systemInstruction = config('gemini.system_instruction');

        if (empty($apiKey)) {
            return $this->fallbackReply($message);
        }

        $modelResource = str_starts_with($model, 'models/') ? $model : 'models/' . $model;
        $endpoint = "{$endpointBase}/" . basename($modelResource) . ":generateContent";

        try {
            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $message],
                        ],
                    ],
                ],
            ];

            if (!empty($systemInstruction)) {
                $payload['systemInstruction'] = [
                    'parts' => [
                        ['text' => $systemInstruction],
                    ],
                ];
            }

            $response = Http::acceptJson()
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->asJson()
                ->post($endpoint, $payload);

            if ($response->ok()) {
                $json = $response->json();
                if (is_array($json)) {
                    if (isset($json['candidates'][0]['content']['parts'])) {
                        $parts = $json['candidates'][0]['content']['parts'];
                        $text = collect($parts)
                            ->pluck('text')
                            ->filter()
                            ->implode('');

                        if ($text !== '') {
                            return trim($text);
                        }
                    }

                    if (isset($json['reply'])) {
                        return (string) $json['reply'];
                    }
                }

                return is_string($json) ? $json : ($json['message'] ?? $this->fallbackReply($message));
            }

            Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);

            if ($response->status() === 402) {
                return 'Gemini no tiene cuota disponible en este momento. Puedo ayudarte con la subida del PDF y el flujo de impresión.';
            }

            if ($response->status() === 401) {
                return 'Gemini no está autorizado en este momento. Revisa la API key.';
            }

            if ($response->status() === 400) {
                return 'La petición a Gemini no fue válida. Revisa el modelo o el formato de la configuración.';
            }

            return $this->fallbackReply($message);
        } catch (\Exception $e) {
            Log::error('Gemini request failed', ['error' => $e->getMessage()]);
            return $this->fallbackReply($message);
        }
    }

    private function fallbackReply(string $message): string
    {
        return "Recibí tu mensaje: {$message}. En este momento no pude consultar la IA, pero puedo ayudarte con la impresión.";
    }
}
