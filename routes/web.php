<?php

use App\Http\Controllers\SaleMediaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sale-media', SaleMediaController::class)
    ->name('sale-media.show');
