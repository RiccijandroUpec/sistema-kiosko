<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiVisionService
{
    /**
     * Envía una imagen a Gemini y extrae el número de referencia y el monto.
     * Devuelve un arreglo o null en caso de fallo.
     */
    public function extractReceiptData(string $base64Image, string $mimeType = 'image/jpeg'): ?array
    {
        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            Log::error('GEMINI_API_KEY no configurada');
            return null;
        }

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => 'Eres un sistema de verificación de pagos. Analiza este comprobante bancario de transferencia. ' .
                                      'Extrae el número de referencia/comprobante/documento y el monto total transferido. ' .
                                      'Tu respuesta DEBE ser EXCLUSIVAMENTE un objeto JSON válido con esta estructura exacta: ' .
                                      '{"referencia": "numero_aqui", "monto": 0.00}. ' .
                                      'El monto debe ser un número con decimales (ej: 0.25). Si no encuentras la referencia, pon "". ' .
                                      'No incluyas markdown (como ```json) ni ningún texto extra.'
                        ],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => base64_encode($base64Image)
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1, // Baja temperatura para mayor precisión
                'responseMimeType' => 'application/json' // Forzar que responda en JSON (Gemini lo soporta)
            ]
        ];

        try {
            $response = Http::timeout(15)->post($endpoint, $payload);

            if ($response->successful()) {
                $json = $response->json();
                
                if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                    $text = trim($json['candidates'][0]['content']['parts'][0]['text']);
                    
                    // Limpiar en caso de que Gemini haya incluido markdown accidentalmente
                    $text = str_replace(['```json', '```'], '', $text);
                    
                    $data = json_decode(trim($text), true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && isset($data['monto'])) {
                        Log::info('Gemini extrajo los datos con éxito', $data);
                        return $data;
                    }
                }
            }
            
            Log::error('Gemini API Error', ['status' => $response->status(), 'body' => $response->body()]);

        } catch (\Exception $e) {
            Log::error('Gemini request failed', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
