<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatbotController;

use App\Http\Controllers\ChatbotApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Client-facing Public API (No CSRF needed since it's an API route)
Route::post('/chatbot/send', [ChatbotApiController::class, 'sendMessage']);
Route::post('/chatbot/live/request', [ChatbotApiController::class, 'requestLiveChat']);
Route::get('/chatbot/live/poll/{lead_id}', [ChatbotApiController::class, 'pollLiveChat']);
Route::post('/chatbot/live/send', [ChatbotApiController::class, 'sendLiveChatMessage']);

use App\Http\Controllers\LicenseController;

// Chatbot API endpoints for the Widget
Route::prefix('v1')->group(function () {
    Route::post('/license/verify', [LicenseController::class, 'verify']);
    Route::get('/widget/config', [LicenseController::class, 'config']);
    Route::post('/install', [LicenseController::class, 'install']);
    Route::post('/license/sync', [LicenseController::class, 'sync']);
    Route::post('/license/status', [LicenseController::class, 'updateStatus']);
    Route::post('/license/config', [LicenseController::class, 'updateConfig']);
    Route::post('/license/reset', [LicenseController::class, 'resetData']);
    Route::delete('/license/{key}', [LicenseController::class, 'destroy']);
    Route::post('/chat/send', [ChatbotController::class, 'send']);
    Route::post('/chat/live/request', [ChatbotController::class, 'requestLiveChat']);
    Route::post('/chat/live/poll', [ChatbotController::class, 'pollLiveChat']);
    Route::post('/chat/live/send', [ChatbotController::class, 'sendLiveChatMessage']);
});
Route::get('/test_db', function() { return \App\Models\Client::all(); });
