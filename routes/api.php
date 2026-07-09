<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatbotController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Chatbot API endpoints for the Widget
Route::prefix('v1')->group(function () {
    Route::post('/chat/send', [ChatbotController::class, 'send']);
    Route::post('/chat/live/request', [ChatbotController::class, 'requestLiveChat']);
    Route::post('/chat/live/poll', [ChatbotController::class, 'pollLiveChat']);
    Route::post('/chat/live/send', [ChatbotController::class, 'sendLiveChatMessage']);
});
