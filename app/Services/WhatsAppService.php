<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp message through Evolution API.
     */
    public function sendMessage(string $to, string $body): array
    {
        $baseUrl = rtrim((string) config('evolution.base_url'), '/');
        $apiKey = (string) config('evolution.api_key');
        $instance = (string) config('evolution.instance');
        $number = $this->normalizePhone($to);

        if ($baseUrl === '' || $apiKey === '' || $instance === '') {
            Log::warning('Evolution API config missing, skipping WhatsApp send');
            return [];
        }

        if ($number === '') {
            Log::warning('Invalid destination phone for Evolution API', ['to' => $to]);
            return [];
        }

        $url = "{$baseUrl}/message/sendText/{$instance}";

        $response = Http::withHeaders([
            'apikey' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->post($url, [
                'number' => $number,
                'text' => $body,
            ]);

        if (!$response->ok()) {
            Log::error('Evolution API send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
            ]);
            throw new \Exception('Evolution API error: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Download media from a URL, first with Evolution API auth headers, then without headers.
     */
    public function downloadFile(string $url): string
    {
        $apiKey = (string) config('evolution.api_key');

        if ($apiKey !== '') {
            $withAuth = Http::withHeaders(['apikey' => $apiKey])->get($url);
            if ($withAuth->ok()) {
                return $withAuth->body();
            }
        }

        $publicResponse = Http::get($url);
        if (!$publicResponse->ok()) {
            throw new \Exception('No se pudo descargar el archivo multimedia de Evolution API.');
        }

        return $publicResponse->body();
    }

    private function normalizePhone(string $input): string
    {
        $normalized = trim($input);
        $normalized = str_replace(['whatsapp:', '@s.whatsapp.net'], '', $normalized);

        return preg_replace('/\D+/', '', $normalized) ?? '';
    }
}
