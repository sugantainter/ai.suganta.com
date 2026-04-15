<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any?}', fn () => view('spa'))
    ->middleware('auth.check')
    ->where('any', '^(?!sanctum).*$');
