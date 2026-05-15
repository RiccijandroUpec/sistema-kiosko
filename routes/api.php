<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PrintJobApiController;
use App\Http\Controllers\Api\KioskApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Print Jobs API
Route::apiResource('print-jobs', PrintJobApiController::class);
Route::patch('/print-jobs/{id}/status', [PrintJobApiController::class, 'updateStatus']);
Route::get('/print-jobs/statistics', [PrintJobApiController::class, 'statistics']);

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// WhatsApp Webhook (Evolution API)
Route::post('/whatsapp/webhook', [\App\Http\Controllers\WhatsAppController::class, 'webhook']);
Route::get('/whatsapp/webhook', [\App\Http\Controllers\WhatsAppController::class, 'webhook']);

// Kiosk API
Route::prefix('kiosk')->group(function () {
    Route::post('/authenticate', [KioskApiController::class, 'authenticate']);
    Route::post('/heartbeat', [KioskApiController::class, 'heartbeat']);
    Route::get('/jobs/pending', [KioskApiController::class, 'pendingJobs']);
    Route::get('/jobs/{printJob}', [KioskApiController::class, 'showJob']);
    Route::get('/jobs/{printJob}/pdf', [KioskApiController::class, 'downloadPdf']);
    Route::post('/jobs/{printJob}/printing', [KioskApiController::class, 'markPrinting']);
    Route::post('/jobs/{printJob}/complete', [KioskApiController::class, 'completeJob']);
});
