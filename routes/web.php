<?php

use App\Http\Controllers\KaaloHomeController;
use App\Http\Controllers\Seo\DynamicSeoController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [KaaloHomeController::class, 'index'])->name('kaalo.home');

Route::middleware('auth.check')->group(function (): void {
    Route::get('/c/{conversationId}', [SpaController::class, 'index'])
        ->where('conversationId', '[A-Za-z0-9\-]+');
    Route::get('/settings', [SpaController::class, 'settings']);
    Route::get('/contact', [SpaController::class, 'fallback']);
    Route::get('/feedback', [SpaController::class, 'fallback']);
});

Route::get('/share/{shareToken}', [SpaController::class, 'share'])
    ->name('chat.share');

Route::get('/{slug}', [DynamicSeoController::class, 'render'])
    ->where('slug', '(?!sanctum|api|up|sitemap\.xml$).+');
