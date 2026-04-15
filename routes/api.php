<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['api.key.auth', 'throttle:ai-chat'])
    ->group(function (): void {
        Route::post('/chat', [ChatController::class, 'chat']);
        Route::get('/chat/histories', [ChatController::class, 'histories']);
        Route::get('/chat/history/{conversationId}', [ChatController::class, 'history']);
        Route::get('/usage', [ChatController::class, 'usage']);
        Route::get('/models', [ChatController::class, 'models']);
        Route::get('/settings/overview', [SettingsController::class, 'overview']);
        Route::get('/provider-keys', [SettingsController::class, 'providerKeys']);
        Route::post('/provider-keys', [SettingsController::class, 'storeProviderKey']);
        Route::put('/settings/password', [SettingsController::class, 'updatePassword']);
        Route::patch('/settings/password', [SettingsController::class, 'updatePassword']);
    });
