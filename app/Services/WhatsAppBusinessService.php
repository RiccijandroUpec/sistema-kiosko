<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppBusinessService
{
    protected string $token;
    protected string $phoneId;
    protected string $accountId;
    protected string $apiVersion;
    protected string $baseUrl;

    public function __construct()
    {
        $this->token = config('whatsapp-business.token');
        $this->phoneId = config('whatsapp-business.phone_id');
        $this->accountId = config('whatsapp-business.account_id');
        $this->apiVersion = config('whatsapp-business.api_version');
        $this->baseUrl = config('whatsapp-business.base_url');
    }

    /**
     * Enviar mensaje de texto a un número de WhatsApp
     */
    public function sendMessage(string $phoneNumber, string $message): bool
    {
        if (empty($this->token) || empty($this->phoneId)) {
            Log::warning('WhatsApp Business credentials not configured');
            return false;
        }

        try {
            $normalizedPhone = $this->normalizePhone($phoneNumber);

            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/{$this->apiVersion}/{$this->phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $normalizedPhone,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent', ['to' => $normalizedPhone, 'message' => $message]);
                return true;
            }

            Log::error('WhatsApp API error', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Enviar mensaje de plantilla (template)
     */
    public function sendTemplate(string $phoneNumber, string $templateName, array $parameters = []): bool
    {
        if (empty($this->token) || empty($this->phoneId)) {
            Log::warning('WhatsApp Business credentials not configured');
            return false;
        }

        try {
            $normalizedPhone = $this->normalizePhone($phoneNumber);

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $normalizedPhone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                ],
            ];

            if (!empty($parameters)) {
                $payload['template']['parameters'] = [
                    'body' => [
                        'parameters' => array_map(fn($p) => ['text' => $p], $parameters),
                    ],
                ];
            }

            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/{$this->apiVersion}/{$this->phoneId}/messages", $payload);

            if ($response->successful()) {
                Log::info('WhatsApp template sent', ['to' => $normalizedPhone, 'template' => $templateName]);
                return true;
            }

            Log::error('WhatsApp template error', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp template send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Enviar documento (PDF, imagen, etc)
     */
    public function sendDocument(string $phoneNumber, string $documentUrl, string $caption = ''): bool
    {
        if (empty($this->token) || empty($this->phoneId)) {
            Log::warning('WhatsApp Business credentials not configured');
            return false;
        }

        try {
            $normalizedPhone = $this->normalizePhone($phoneNumber);

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $normalizedPhone,
                'type' => 'document',
                'document' => [
                    'link' => $documentUrl,
                ],
            ];

            if (!empty($caption)) {
                $payload['document']['caption'] = $caption;
            }

            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/{$this->apiVersion}/{$this->phoneId}/messages", $payload);

            if ($response->successful()) {
                Log::info('WhatsApp document sent', ['to' => $normalizedPhone]);
                return true;
            }

            Log::error('WhatsApp document error', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp document send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Marcar mensaje como leído
     */
    public function markAsRead(string $messageId): bool
    {
        if (empty($this->token) || empty($this->phoneId)) {
            return false;
        }

        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/{$this->apiVersion}/{$this->phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'status' => 'read',
                    'message_id' => $messageId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WhatsApp mark as read failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Normalizar número de teléfono
     * Elimina caracteres especiales y formatos
     */
    protected function normalizePhone(string $phone): string
    {
        // Elimina espacios, guiones, paréntesis, etc
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Si no tiene +, lo agrega
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Obtener el número de teléfono del business
     */
    public function getPhoneNumber(): string
    {
        return config('whatsapp-business.phone_number') ?? '';
    }

    /**
     * Validar credenciales
     */
    public function validateCredentials(): bool
    {
        if (empty($this->token) || empty($this->phoneId)) {
            return false;
        }

        try {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/{$this->apiVersion}/{$this->phoneId}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WhatsApp credential validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
