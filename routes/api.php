<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['throttle:ai-chat'])
    ->group(function (): void {
        Route::get('/public/chat/share/{shareToken}', [ChatController::class, 'sharedHistory']);
    });

Route::prefix('v1')
    ->middleware(['api.key.auth', 'throttle:ai-chat'])
    ->group(function (): void {
        Route::post('/chat', [ChatController::class, 'chat']);
        Route::post('/chat/feedback', [ChatController::class, 'feedback']);
        Route::post('/chat/async', [ChatController::class, 'chatAsync']);
        Route::get('/chat/jobs/{jobId}', [ChatController::class, 'chatJobStatus'])
            ->middleware('throttle:ai-chat-poll');
        Route::get('/chat/histories', [ChatController::class, 'histories']);
        Route::get('/chat/histories/search', [ChatController::class, 'searchHistories']);
        Route::get('/chat/history/{conversationId}', [ChatController::class, 'history']);
        Route::delete('/chat/history/{conversationId}', [ChatController::class, 'deleteHistory']);
        Route::post('/chat/history/{conversationId}/share', [ChatController::class, 'share']);
        Route::get('/chat/history/{conversationId}/assets', [ChatController::class, 'assets']);
        Route::get('/chat/history/{conversationId}/assets/{assetId}/signed-url', [ChatController::class, 'signedAssetUrl']);
        Route::get('/chat/history/{conversationId}/assets/{assetId}/download', [ChatController::class, 'downloadAsset'])
            ->name('api.v1.chat.assets.download');
        Route::get('/usage', [ChatController::class, 'usage']);
        Route::get('/models', [ChatController::class, 'models']);
        Route::get('/settings/overview', [SettingsController::class, 'overview']);
        Route::get('/settings/uploads', [SettingsController::class, 'uploads']);
        Route::put('/settings/profile', [SettingsController::class, 'updateProfile']);
        Route::patch('/settings/profile', [SettingsController::class, 'updateProfile']);
        Route::get('/provider-keys', [SettingsController::class, 'providerKeys']);
        Route::post('/provider-keys', [SettingsController::class, 'storeProviderKey']);
        Route::delete('/provider-keys/{provider}', [SettingsController::class, 'deleteProviderKey']);
        Route::put('/settings/password', [SettingsController::class, 'updatePassword']);
        Route::patch('/settings/password', [SettingsController::class, 'updatePassword']);
    });
