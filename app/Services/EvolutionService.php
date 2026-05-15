<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EvolutionService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $instance;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('evolution.base_url', 'http://127.0.0.1:8080'), '/');
        $this->apiKey = config('evolution.api_key');
        $this->instance = config('evolution.instance', 'kiosko');
    }

    /**
     * Enviar mensaje de texto
     */
    public function sendMessage(string $number, string $text)
    {
        try {
            $cleanNumber = $this->formatNumber($number);
            
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/message/sendText/{$this->instance}", [
                'number' => $cleanNumber,
                'options' => [
                    'delay' => 1200,
                    'presence' => 'composing'
                ],
                'text' => $text
            ]);

            if (!$response->successful()) {
                Log::error('Evolution API Send Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'number' => $cleanNumber
                ]);
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Evolution API Error (SendText): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Marcar como leído
     */
    public function markAsRead(string $messageKey)
    {
        try {
            Http::withHeaders(['apikey' => $this->apiKey])
                ->post("{$this->baseUrl}/chat/markMessageAsRead/{$this->instance}", [
                    'readMessages' => [$messageKey]
                ]);
        } catch (\Exception $e) {
            Log::error('Evolution API Error (MarkRead): ' . $e->getMessage());
        }
    }

    /**
     * Descargar media (PDF)
     */
    public function downloadMedia(string $messageId)
    {
        try {
            $response = Http::withHeaders(['apikey' => $this->apiKey])
                ->post("{$this->baseUrl}/chat/getBase64FromMediaMessage/{$this->instance}", [
                    'message' => [
                        'key' => [
                            'id' => $messageId
                        ]
                    ],
                    'convertToMp4' => false
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $base64 = $data['base64'] ?? null;
                if ($base64) {
                    // Limpiar prefijo base64 si existe
                    if (str_contains($base64, ',')) {
                        $base64 = explode(',', $base64)[1];
                    }
                    return base64_decode($base64);
                }
            } else {
                Log::error('Evolution API DownloadMedia Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'messageId' => $messageId
                ]);
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Evolution API Error (DownloadMedia): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validar conexión con la API
     */
    public function validateCredentials(): bool
    {
        try {
            $response = Http::withHeaders(['apikey' => $this->apiKey])
                ->get("{$this->baseUrl}/instance/fetchInstances", [
                    'instanceName' => $this->instance
                ]);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Formatear número para WhatsApp
     */
    protected function formatNumber(string $number): string
    {
        $clean = preg_replace('/[^0-9]/', '', $number);
        // Si no tiene código de país, podrías añadir 593 aquí por defecto si lo deseas
        return $clean;
    }
}
