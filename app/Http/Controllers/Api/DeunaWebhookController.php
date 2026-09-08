<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeunaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeunaWebhookController extends Controller
{
    protected DeunaService $deunaService;

    public function __construct(DeunaService $deunaService)
    {
        $this->deunaService = $deunaService;
    }

    /**
     * Endpoint para recibir webhooks de DeUna.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $result = $this->deunaService->processWebhook($payload);

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
