<?php

use App\Http\Controllers\API\AccountController;
use App\Http\Controllers\API\AccountDetailsController;
use App\Http\Controllers\API\AccountReceivedController;
use App\Http\Controllers\API\AdminBootstrapController;
use App\Http\Controllers\API\ApproveSaleOrderController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BonusController;
use App\Http\Controllers\API\CarController;
use App\Http\Controllers\API\CarModelController;
use App\Http\Controllers\API\CarSaleStateController;
use App\Http\Controllers\API\CompleteSaleController;
use App\Http\Controllers\API\DeductionController;
use App\Http\Controllers\API\GalleryController;
use App\Http\Controllers\API\GalleryPhoneController;
use App\Http\Controllers\API\MarketController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\SaleController;
use App\Http\Controllers\API\SaleOrderController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserPasswordController;
use App\Http\Controllers\API\WeeklyAccountController;
use App\Http\Controllers\API\WeekController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/bootstrap-admin', AdminBootstrapController::class);

Route::get('galleries', [GalleryController::class, 'index']);
Route::get('galleries/{gallery}', [GalleryController::class, 'show']);
Route::get('gallery-phones', [GalleryPhoneController::class, 'index']);
Route::get('gallery-phones/{galleryPhone}', [GalleryPhoneController::class, 'show']);
Route::get('markets', [MarketController::class, 'index']);
Route::get('markets/{market}', [MarketController::class, 'show']);
Route::get('car-models', [CarModelController::class, 'index']);
Route::get('car-models/{carModel}', [CarModelController::class, 'show']);
Route::get('cars', [CarController::class, 'index']);
Route::get('cars/{car}', [CarController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    Route::put('users/{user}/password', UserPasswordController::class);

    Route::post('galleries', [GalleryController::class, 'store']);
    Route::put('galleries/{gallery}', [GalleryController::class, 'update']);
    Route::delete('galleries/{gallery}', [GalleryController::class, 'destroy']);

    Route::post('gallery-phones', [GalleryPhoneController::class, 'store']);
    Route::put('gallery-phones/{galleryPhone}', [GalleryPhoneController::class, 'update']);
    Route::delete('gallery-phones/{galleryPhone}', [GalleryPhoneController::class, 'destroy']);

    Route::post('markets', [MarketController::class, 'store']);
    Route::put('markets/{market}', [MarketController::class, 'update']);
    Route::delete('markets/{market}', [MarketController::class, 'destroy']);

    Route::post('car-models', [CarModelController::class, 'store']);
    Route::put('car-models/{carModel}', [CarModelController::class, 'update']);
    Route::delete('car-models/{carModel}', [CarModelController::class, 'destroy']);

    Route::post('cars', [CarController::class, 'store']);
    Route::put('cars/{car}', [CarController::class, 'update']);
    Route::put('cars/{car}/sale-state', CarSaleStateController::class);
    Route::delete('cars/{car}', [CarController::class, 'destroy']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::put('orders/{order}', [OrderController::class, 'update']);
    Route::delete('orders/{order}', [OrderController::class, 'destroy']);

    Route::get('weeks', [WeekController::class, 'index']);

    Route::get('sales', [SaleController::class, 'index']);
    Route::post('sales', [SaleController::class, 'store']);
    Route::get('sales/{sale}', [SaleController::class, 'show']);
    Route::put('sales/{sale}', [SaleController::class, 'update']);
    Route::put('sales/{sale}/complete', CompleteSaleController::class);
    Route::put('sales/{sale}/approve-order', ApproveSaleOrderController::class);
    Route::delete('sale-orders/{sale}', SaleOrderController::class);

    Route::get('accounts/weekly', [WeeklyAccountController::class, 'index']);
    Route::get('accounts/{account}/details', AccountDetailsController::class);
    Route::put('accounts/{account}/received', AccountReceivedController::class);
    Route::get('accounts/{account}', [AccountController::class, 'show']);
    Route::put('accounts/{account}', [AccountController::class, 'update']);

    Route::post('accounts/{account}/bonuses', [BonusController::class, 'store']);
    Route::put('bonuses/{bonus}', [BonusController::class, 'update']);
    Route::delete('bonuses/{bonus}', [BonusController::class, 'destroy']);
    Route::post('accounts/{account}/deductions', [DeductionController::class, 'store']);
    Route::put('deductions/{deduction}', [DeductionController::class, 'update']);
    Route::delete('deductions/{deduction}', [DeductionController::class, 'destroy']);
});
