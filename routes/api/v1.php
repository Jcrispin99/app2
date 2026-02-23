<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AttributeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\JournalController;
use App\Http\Controllers\Api\V1\MembershipPlanController;
use App\Http\Controllers\Api\V1\MembershipSubscriptionController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\PosConfigController;
use App\Http\Controllers\Api\V1\PosSessionController;
use App\Http\Controllers\Api\V1\PosSessionPaymentController;
use App\Http\Controllers\Api\V1\ProductTemplateController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\SubscriptionFreezeController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\TaxController;
use App\Http\Controllers\Api\V1\UnitOfMeasureController;
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

    Route::get('journals/form-options', [JournalController::class, 'formOptions']);
    Route::post('journals/{journal}/reset-sequence', [JournalController::class, 'resetSequence']);
    Route::apiResource('journals', JournalController::class);

    Route::get('taxes/form-options', [TaxController::class, 'formOptions']);
    Route::patch('taxes/{tax}/toggle-status', [TaxController::class, 'toggleStatus']);
    Route::apiResource('taxes', TaxController::class);

    Route::get('unit-of-measures/form-options', [UnitOfMeasureController::class, 'formOptions']);
    Route::patch('unit-of-measures/{unit_of_measure}/toggle-status', [UnitOfMeasureController::class, 'toggleStatus']);
    Route::apiResource('unit-of-measures', UnitOfMeasureController::class);

    Route::get('membership-plans/form-options', [MembershipPlanController::class, 'formOptions']);
    Route::patch('membership-plans/{membership_plan}/toggle-status', [MembershipPlanController::class, 'toggleStatus']);
    Route::apiResource('membership-plans', MembershipPlanController::class);

    // Membership Subscriptions & Nested Freezes
    Route::apiResource('membership-subscriptions', MembershipSubscriptionController::class)->except(['update', 'destroy']);

    // Freeze Action Routes
    Route::post('membership-subscriptions/{subscription}/freezes', [SubscriptionFreezeController::class, 'store']);
    Route::patch('membership-freezes/{freeze}/complete', [SubscriptionFreezeController::class, 'complete']);
    Route::patch('membership-freezes/{freeze}/cancel', [SubscriptionFreezeController::class, 'cancel']);
    /*
     * --------------------------------------------------------------------------
     * Asistencias (Attendance - Check-in / Check-out Model)
     * --------------------------------------------------------------------------
     */
    Route::get('attendances', [App\Http\Controllers\Api\V1\AttendanceController::class, 'index']);
    Route::get('attendances/{attendance}', [App\Http\Controllers\Api\V1\AttendanceController::class, 'show']);
    Route::post('attendances/check-in', [App\Http\Controllers\Api\V1\AttendanceController::class, 'checkIn']);
    Route::patch('attendances/{attendance}/check-out', [App\Http\Controllers\Api\V1\AttendanceController::class, 'checkOut']);

    /*
     * --------------------------------------------------------------------------
     * Punto de Venta (POS) - Foundations
     * --------------------------------------------------------------------------
     */
    Route::patch('payment-methods/{payment_method}/toggle-status', [PaymentMethodController::class, 'toggleStatus']);
    Route::apiResource('payment-methods', PaymentMethodController::class);

    Route::patch('pos-configs/{pos_config}/toggle-status', [PosConfigController::class, 'toggleStatus']);
    Route::apiResource('pos-configs', PosConfigController::class);

    /*
     * --------------------------------------------------------------------------
     * Punto de Venta (POS) - Turnos
     * --------------------------------------------------------------------------
     */
    Route::post('pos-sessions', [PosSessionController::class, 'open']);
    Route::patch('pos-sessions/{pos_session}/close', [PosSessionController::class, 'close']);
    Route::get('pos-sessions', [PosSessionController::class, 'index']);
    Route::get('pos-sessions/{pos_session}', [PosSessionController::class, 'show']);

    Route::get('pos-session-payments', [PosSessionPaymentController::class, 'index']);

});

// Password reset routes (public with rate limiting)
Route::middleware('throttle:6,1')->group(function (): void {
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
        ->name('password.email');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])
        ->name('password.reset');
});
Route::get('members/form-options', [App\Http\Controllers\Api\V1\MemberController::class, 'formOptions']);
Route::post('members/{member}/activate-portal', [App\Http\Controllers\Api\V1\MemberController::class, 'activatePortal']);
Route::apiResource('members', App\Http\Controllers\Api\V1\MemberController::class);
