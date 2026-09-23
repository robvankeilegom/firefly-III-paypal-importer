<?php

use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/version', function () {
    return config('app.version');
});

Route::get('/', [WelcomeController::class, 'index']);
