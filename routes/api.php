<?php

use App\Http\Controllers\API\AccountController;
use App\Http\Controllers\API\AccountDetailsController;
use App\Http\Controllers\API\AccountReceivedController;
use App\Http\Controllers\API\ActivityLogController;
use App\Http\Controllers\API\AdminBootstrapController;
use App\Http\Controllers\API\ApproveOrderController;
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
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\OrderCheckedController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PropertyCategoryController;
use App\Http\Controllers\API\PropertyController;
use App\Http\Controllers\API\PropertyImageController;
use App\Http\Controllers\API\PropertySubcategoryController;
use App\Http\Controllers\API\PropertySubmissionController;
use App\Http\Controllers\API\ProvinceController;
use App\Http\Controllers\API\RealEstateOfficeController;
use App\Http\Controllers\API\RealEstateOfficePhoneController;
use App\Http\Controllers\API\RealEstateOptionsController;
use App\Http\Controllers\API\RejectOrderController;
use App\Http\Controllers\API\SaleController;
use App\Http\Controllers\API\SaleInstallmentContractController;
use App\Http\Controllers\API\SaleOrderController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserPasswordController;
use App\Http\Controllers\API\WeekController;
use App\Http\Controllers\API\WeeklyAccountController;
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
Route::get('cars/latest', [CarController::class, 'latest']);
Route::get('cars/{car}', [CarController::class, 'show']);
Route::get('provinces', [ProvinceController::class, 'index']);
Route::get('provinces/{province}', [ProvinceController::class, 'show']);
Route::prefix('real-estate')->group(function (): void {
    Route::get('options', RealEstateOptionsController::class);
    Route::get('offices', [RealEstateOfficeController::class, 'index']);
    Route::get('offices/{realEstateOffice}', [RealEstateOfficeController::class, 'show']);
    Route::get('office-phones', [RealEstateOfficePhoneController::class, 'index']);
    Route::get('office-phones/{realEstateOfficePhone}', [RealEstateOfficePhoneController::class, 'show']);
    Route::get('property-categories', [PropertyCategoryController::class, 'index']);
    Route::get('property-subcategories', [PropertySubcategoryController::class, 'index']);
    Route::get('properties', [PropertyController::class, 'index']);
    Route::get('properties/{property}', [PropertyController::class, 'show']);
});

Route::post('real-estate/property-submissions', [PropertySubmissionController::class, 'store']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    Route::put('users/{user}/password', UserPasswordController::class);
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::put('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

    Route::post('galleries', [GalleryController::class, 'store']);
    Route::put('galleries/{gallery}', [GalleryController::class, 'update']);
    Route::delete('galleries/{gallery}', [GalleryController::class, 'destroy']);

    Route::post('gallery-phones', [GalleryPhoneController::class, 'store']);
    Route::put('gallery-phones/{galleryPhone}', [GalleryPhoneController::class, 'update']);
    Route::delete('gallery-phones/{galleryPhone}', [GalleryPhoneController::class, 'destroy']);

    Route::post('markets', [MarketController::class, 'store']);
    Route::put('markets/{market}', [MarketController::class, 'update']);
    Route::delete('markets/{market}', [MarketController::class, 'destroy']);

    Route::post('provinces', [ProvinceController::class, 'store']);
    Route::put('provinces/{province}', [ProvinceController::class, 'update']);
    Route::delete('provinces/{province}', [ProvinceController::class, 'destroy']);

    Route::prefix('real-estate')->group(function (): void {
        Route::get('property-submissions', [PropertySubmissionController::class, 'index']);
        Route::get('property-submissions/{submission}', [PropertySubmissionController::class, 'show']);
        Route::put('property-submissions/{submission}/approve', [PropertySubmissionController::class, 'approve']);
        Route::put('property-submissions/{submission}/reject', [PropertySubmissionController::class, 'reject']);

        Route::post('offices', [RealEstateOfficeController::class, 'store']);
        Route::put('offices/{realEstateOffice}', [RealEstateOfficeController::class, 'update']);
        Route::delete('offices/{realEstateOffice}', [RealEstateOfficeController::class, 'destroy']);

        Route::post('office-phones', [RealEstateOfficePhoneController::class, 'store']);
        Route::put('office-phones/{realEstateOfficePhone}', [RealEstateOfficePhoneController::class, 'update']);
        Route::delete('office-phones/{realEstateOfficePhone}', [RealEstateOfficePhoneController::class, 'destroy']);

        Route::post('properties', [PropertyController::class, 'store']);
        Route::put('properties/{property}', [PropertyController::class, 'update']);
        Route::delete('properties/{property}', [PropertyController::class, 'destroy']);
        Route::post('properties/{property}/images', [PropertyImageController::class, 'store']);
        Route::delete('property-images/{image}', [PropertyImageController::class, 'destroy']);
    });

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
    Route::put('orders/{order}/checked', [OrderCheckedController::class, 'update']);
    Route::put('orders/{order}/approve', ApproveOrderController::class);
    Route::put('orders/{order}/reject', RejectOrderController::class);
    Route::delete('orders/{order}', [OrderController::class, 'destroy']);

    Route::get('weeks', [WeekController::class, 'index']);

    Route::get('sales', [SaleController::class, 'index']);
    Route::post('sales', [SaleController::class, 'store']);
    Route::get('sales/{sale}', [SaleController::class, 'show']);
    Route::put('sales/{sale}', [SaleController::class, 'update']);
    Route::put('sales/{sale}/installment-contract', SaleInstallmentContractController::class);
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
