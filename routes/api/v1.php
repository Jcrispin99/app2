<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AttributeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\ProductTemplateController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Routes for API version 1.
|
*/

// Public routes with auth rate limiter (5/min - brute force protection)
Route::middleware('throttle:auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->name('api.v1.register');
    Route::post('login', [AuthController::class, 'login'])->name('api.v1.login');
});

// Protected routes with authenticated rate limiter (120/min)
Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.logout');
    Route::get('me', [AuthController::class, 'me'])->name('api.v1.me');

    // Email verification
    Route::post('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('email/resend', [AuthController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // endpoinds
    Route::get('companies/form-options', [CompanyController::class, 'formOptions']);
    Route::apiResource('companies', CompanyController::class);

    Route::get('categories/form-options', [CategoryController::class, 'formOptions']);
    Route::apiResource('categories', CategoryController::class);

    Route::get('attributes/form-options', [AttributeController::class, 'formOptions']);
    Route::apiResource('attributes', AttributeController::class);

    Route::patch('products/{product_template}/toggle-status', [ProductTemplateController::class, 'toggleStatus']);
    Route::get('products/form-options', [ProductTemplateController::class, 'formOptions']);
    Route::apiResource('products', ProductTemplateController::class);

    Route::get('warehouses/form-options', [WarehouseController::class, 'formOptions']);
    Route::apiResource('warehouses', WarehouseController::class);

    Route::get('customers/form-options', [CustomerController::class, 'formOptions']);
    Route::apiResource('customers', CustomerController::class);

    Route::get('suppliers/form-options', [SupplierController::class, 'formOptions']);
    Route::apiResource('suppliers', SupplierController::class);

    Route::get('purchases/form-options', [PurchaseController::class, 'formOptions']);
    Route::post('purchases/{purchase}/post', [PurchaseController::class, 'post']);
    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel']);
    Route::apiResource('purchases', PurchaseController::class);

    Route::get('sales/form-options', [SaleController::class, 'formOptions']);
    Route::post('sales/{sale}/post', [SaleController::class, 'post']);
    Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel']);
    Route::post('sales/{sale}/credit-note', [SaleController::class, 'createCreditNote']);
    Route::apiResource('sales', SaleController::class);
});

// Password reset routes (public with rate limiting)
Route::middleware('throttle:6,1')->group(function (): void {
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
        ->name('password.email');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])
        ->name('password.reset');
});
