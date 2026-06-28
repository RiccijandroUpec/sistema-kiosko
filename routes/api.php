<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\KioskApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// WhatsApp Webhook (Evolution API)
Route::middleware('throttle:120,1')->group(function () {
    Route::post('/whatsapp/webhook', [\App\Http\Controllers\WhatsAppController::class, 'webhook']);
    Route::get('/whatsapp/webhook', [\App\Http\Controllers\WhatsAppController::class, 'webhook']);
});

// Kiosk API
Route::prefix('kiosk')->middleware('throttle:60,1')->group(function () {
    Route::post('/authenticate', [KioskApiController::class, 'authenticate']);
    Route::post('/heartbeat', [KioskApiController::class, 'heartbeat']);
    Route::get('/jobs/pending', [KioskApiController::class, 'pendingJobs']);
    Route::get('/jobs/{printJob}', [KioskApiController::class, 'showJob']);
    Route::get('/jobs/{printJob}/pdf', [KioskApiController::class, 'downloadPdf']);
    Route::post('/jobs/{printJob}/printing', [KioskApiController::class, 'markPrinting']);
    Route::post('/jobs/{printJob}/complete', [KioskApiController::class, 'completeJob']);
    
    // Rutas nuevas para manejo de errores
    Route::post('/jobs/{printJob}/error', [KioskApiController::class, 'reportError']);
});
