<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp message using Twilio REST API.
     * `to` must be in Twilio format: whatsapp:+<countrycode><number>
     */
    public function sendMessage(string $to, string $body): array
    {
        $sid = config('twilio.account_sid');
        $token = config('twilio.auth_token');
        $from = config('twilio.from_whatsapp');

        if (empty($sid) || empty($token) || empty($from)) {
            Log::warning('Twilio config missing, skipping WhatsApp send');
            return [];
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post($url, [
                'To' => $to,
                'From' => $from,
                'Body' => $body,
            ]);

        if (!$response->ok()) {
            Log::error('Twilio send failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Twilio error: ' . $response->body());
        }

        return $response->json();
    }
}
