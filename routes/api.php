<?php

use App\Http\Controllers\Api\ChatController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['api.key.auth', 'throttle:ai-chat'])
    ->group(function (): void {
        Route::post('/chat', [ChatController::class, 'chat']);
        Route::get('/chat/histories', [ChatController::class, 'histories']);
        Route::get('/chat/history/{conversationId}', [ChatController::class, 'history']);
        Route::get('/usage', [ChatController::class, 'usage']);
        Route::get('/models', [ChatController::class, 'models']);
    });
