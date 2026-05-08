<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PrintJobApiController;

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
