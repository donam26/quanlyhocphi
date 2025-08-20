<?php
use Illuminate\Support\Facades\Route;

// Public routes (không cần authentication)
Route::prefix('api/public')->name('public.')->group(function () {
    // SePay Webhook - URL chuẩn cho webhook
    Route::post('/webhook/sepay', [\App\Http\Controllers\Api\SePayWebhookController::class, 'handleWebhook'])
        ->name('webhook.sepay');

    // Public payment info endpoint
    Route::get('/payment/{paymentId}', [\App\Http\Controllers\PublicPaymentController::class, 'getPaymentInfo'])
        ->name('payment.info');
});