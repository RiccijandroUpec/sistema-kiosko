<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\DeepseekService;
use App\Services\WhatsAppService;

class WhatsAppController extends Controller
{
    public function webhook(Request $request, DeepseekService $deepseek, WhatsAppService $wa)
    {
        $from = $request->input('From'); // e.g. "whatsapp:+123456789"
        $body = $request->input('Body', '');

        Log::info('Twilio webhook received', ['from' => $from, 'body' => $body]);

        if (empty($body) || empty($from)) {
            return response('OK', 200);
        }

        try {
            // Get reply from Deepseek
            $reply = $deepseek->chat($body, ['from' => $from]);

            // Send reply back to sender
            $wa->sendMessage($from, $reply);
        } catch (\Exception $e) {
            Log::error('Error handling Twilio webhook', ['error' => $e->getMessage()]);
        }

        return response('OK', 200);
    }
}
