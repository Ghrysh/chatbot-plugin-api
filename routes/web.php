<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Embeddable Iframe Views
Route::get('/embed/chatbot', [DashboardController::class, 'embedChatbot'])
    ->name('embed.chatbot')
    ->withoutMiddleware([\Illuminate\Http\Middleware\FrameGuard::class]);
Route::get('/embed/livechat', [DashboardController::class, 'embedLivechat'])
    ->name('embed.livechat')
    ->withoutMiddleware([\Illuminate\Http\Middleware\FrameGuard::class]);

use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\LiveChatAdminController;

// Web endpoints for Dashboard
// Route::middleware(['auth', 'verified'])->group(function () {
    // Knowledge Base CRUD
    Route::post('/knowledge', [KnowledgeController::class, 'store'])->name('knowledge.store');
    Route::put('/knowledge/{id}', [KnowledgeController::class, 'update'])->name('knowledge.update');
    Route::delete('/knowledge/{id}', [KnowledgeController::class, 'destroy'])->name('knowledge.destroy');

    // Live Chat AJAX Endpoints
    Route::get('/livechat/poll', [LiveChatAdminController::class, 'poll'])->name('livechat.poll');
    Route::patch('/livechat/{id}/status', [LiveChatAdminController::class, 'updateStatus'])->name('livechat.status');
    Route::get('/livechat/{id}/history', [LiveChatAdminController::class, 'getHistory'])->name('livechat.history');
    Route::post('/livechat/send', [LiveChatAdminController::class, 'replyMessage'])->name('livechat.send');
    Route::post('/livechat/action', [LiveChatAdminController::class, 'action'])->name('livechat.action');
// });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
