<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\PublicPaymentController;
use Illuminate\Support\Facades\Auth;

// Auth Routes (đã được tạo bởi laravel/ui)
Auth::routes(['register' => false]); // Tắt đăng ký, vì chỉ có admin

// Chuyển hướng trang chủ công khai đến trang đăng nhập
Route::get('/', function () {
    return redirect()->route('login');
});

// Payment Gateway Routes (Public - không cần auth)
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/{enrollment}', [PaymentGatewayController::class, 'showPaymentPage'])->name('gateway.show');
    Route::post('/initiate', [PaymentGatewayController::class, 'initiatePayment'])->name('gateway.initiate');
    Route::get('/status/{payment}', [PaymentGatewayController::class, 'checkPaymentStatus'])->name('gateway.status');
    Route::get('/result', [PaymentGatewayController::class, 'paymentResult'])->name('gateway.result');
});

// SePay Webhook (Public - không cần auth)
Route::post('/webhook/sepay', [PaymentGatewayController::class, 'webhook'])->name('webhook.sepay');

// Public Payment Links (không cần auth)
Route::prefix('pay')->name('public.payment.')->group(function () {
    Route::get('/{token}', [PublicPaymentController::class, 'showPaymentPage'])->name('show');
    Route::post('/{token}/create', [PublicPaymentController::class, 'createPayment'])->name('create');
    Route::get('/{token}/status/{paymentId}', [PublicPaymentController::class, 'checkPaymentStatus'])->name('status');
});

// Các routes yêu cầu đăng nhập và quyền admin
Route::middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getDataByTimeRange'])->name('dashboard.data');

});
