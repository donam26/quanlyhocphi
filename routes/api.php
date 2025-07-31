<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Api\SearchController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/healthy', function () {
    return response()->json(['message' => 'API is running']);
});

// API tìm kiếm học viên (không yêu cầu xác thực)
Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('api.search.autocomplete');

// API tìm kiếm khóa học
Route::get('/course-items/search', [CourseItemController::class, 'search'])->name('api.course-items.search');

// API học viên
Route::get('/students/{id}/info', [App\Http\Controllers\Api\StudentController::class, 'getInfo']);
Route::get('/enrollments/{id}/info', [App\Http\Controllers\Api\StudentController::class, 'getEnrollmentInfo']);

// API cho cấu trúc cây khóa học mới
Route::get('/course-items', [CourseItemController::class, 'index']);
Route::post('/course-items', [CourseItemController::class, 'store']);
Route::get('/course-items/{id}', [CourseItemController::class, 'show']);
Route::put('/course-items/{id}', [CourseItemController::class, 'update']);
Route::delete('/course-items/{id}', [CourseItemController::class, 'destroy']);

// API cho thanh toán
Route::post('/payments/bulk-action', [PaymentController::class, 'bulkAction'])->name('api.payments.bulk-action');
Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirmPayment']);
Route::post('/payments/{payment}/refund', [PaymentController::class, 'refundPayment']);
Route::get('/payments/{payment}', [PaymentController::class, 'getPaymentInfo']);

