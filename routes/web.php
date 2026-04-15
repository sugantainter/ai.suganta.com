<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any?}', fn () => view('spa'))
    ->where('any', '^(?!sanctum).*$');
