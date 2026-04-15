<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SpaController::class, 'index'])
    ->middleware('auth.check');

Route::get('/settings', [SpaController::class, 'settings'])
    ->middleware('auth.check');

Route::get('/{any}', [SpaController::class, 'fallback'])
    ->middleware('auth.check')
    ->where('any', '^(?!sanctum).*$');
