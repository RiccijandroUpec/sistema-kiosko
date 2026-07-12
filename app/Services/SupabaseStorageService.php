<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseStorageService
{
    private string $url;
    private string $serviceKey;
    private string $bucket;

    public function __construct()
    {
        $this->url = rtrim((string) config('supabase.url'), '/');
        $this->serviceKey = (string) config('supabase.service_key');
        $this->bucket = (string) config('supabase.storage_bucket', 'pdfs');
    }

    /**
     * Sube un archivo al bucket y devuelve el path dentro del bucket, o null si falla.
     */
    public function upload(string $contents, string $path, string $contentType = 'application/pdf'): ?string
    {
        if (empty($this->url) || empty($this->serviceKey)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->serviceKey}",
                'apiKey' => $this->serviceKey,
                'Content-Type' => $contentType,
            ])->withBody($contents, $contentType)
              ->post("{$this->url}/storage/v1/object/{$this->bucket}/{$path}");

            if ($response->successful()) {
                return $path;
            }

            Log::warning('Supabase storage upload unsuccessful', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Supabase storage upload exception: ' . $e->getMessage());
        }

        return null;
    }

    public function publicUrl(string $path): string
    {
        return "{$this->url}/storage/v1/object/public/{$this->bucket}/{$path}";
    }

    public function delete(string $path): bool
    {
        if (empty($this->url) || empty($this->serviceKey)) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->serviceKey}",
                'apiKey' => $this->serviceKey,
            ])->delete("{$this->url}/storage/v1/object/{$this->bucket}/{$path}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Supabase storage delete exception: ' . $e->getMessage());
            return false;
        }
    }
}
